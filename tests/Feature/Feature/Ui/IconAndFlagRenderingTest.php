<?php

namespace Tests\Feature\Feature\Ui;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class IconAndFlagRenderingTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_flag_component_renders_country_and_language_flags(): void
    {
        $countryFlag = (string) $this->blade('<x-ui.flag type="country" code="us" class="size-4" />');
        $languageFlag = (string) $this->blade('<x-ui.flag type="language" code="en" class="size-4" />');

        $this->assertStringContainsString('data-slot="flag"', $countryFlag);
        $this->assertStringContainsString('data-flag-type="country"', $countryFlag);
        $this->assertStringContainsString('data-flag-code="us"', $countryFlag);
        $this->assertStringContainsString('data-flag-type="language"', $languageFlag);
        $this->assertStringContainsString('data-flag-code="en"', $languageFlag);
    }

    public function test_flag_component_suppresses_invalid_codes(): void
    {
        $rendered = trim((string) $this->blade('<x-ui.flag type="country" code="not-a-real-code" />'));

        $this->assertSame('', $rendered);
    }

    public function test_title_page_renders_flag_badges_for_country_and_language_metadata(): void
    {
        $title = $this->sampleTitleWithLocaleMetadata();
        $countryCode = strtolower((string) $title->countries->first()?->code);
        $languageCode = strtolower((string) $title->languages->first()?->code);

        if ($countryCode === '' || $languageCode === '') {
            $this->markTestSkipped('The sampled remote title is missing country or language metadata for flag rendering.');
        }

        $renderedLanguageFlag = trim((string) $this->blade(sprintf(
            '<x-ui.flag type="language" code="%s" class="size-4" />',
            e($languageCode),
        )));

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(strtoupper($countryCode))
            ->assertSee(strtoupper($languageCode))
            ->assertSeeHtml('data-flag-type="country"')
            ->assertSeeHtml('data-flag-code="'.$countryCode.'"');

        if ($renderedLanguageFlag !== '') {
            $response
                ->assertSeeHtml('data-flag-type="language"')
                ->assertSeeHtml('data-flag-code="'.$languageCode.'"');
        }
    }

    public function test_discover_and_people_index_empty_states_render_icon_media(): void
    {
        $this->get(route('public.discover', ['q' => 'this-search-should-not-match-anything']))
            ->assertOk()
            ->assertSee('No titles match the current filters.')
            ->assertSeeHtml('data-slot="empty-media"');

        $this->get(route('public.people.index', ['q' => 'this-search-should-not-match-anything']))
            ->assertOk()
            ->assertSee('No people match the current filters.')
            ->assertSeeHtml('data-slot="empty-media"');
    }

    public function test_ghost_links_render_a_trailing_icon_by_default(): void
    {
        $rendered = (string) $this->blade('<x-ui.link href="/next" variant="ghost">Next</x-ui.link>');

        $this->assertStringContainsString('data-slot="link-icon:after"', $rendered);
    }

    public function test_light_solid_badges_use_contrast_safe_foreground_and_current_color_icons(): void
    {
        $rendered = (string) $this->blade('<x-ui.badge color="amber" icon="star">Featured</x-ui.badge>');

        $this->assertStringContainsString('text-amber-950', $rendered);
        $this->assertStringContainsString('!text-current', $rendered);
    }
}
