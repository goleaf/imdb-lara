<?php

namespace Tests\Feature\Feature\Ui;

use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IconAndFlagRenderingTest extends TestCase
{
    use RefreshDatabase;

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
        $title = Title::factory()->create([
            'origin_country' => 'US, LT',
            'original_language' => 'en',
        ]);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('US')
            ->assertSee('LT')
            ->assertSee('EN')
            ->assertSeeHtml('data-flag-type="country"')
            ->assertSeeHtml('data-flag-code="us"')
            ->assertSeeHtml('data-flag-code="lt"')
            ->assertSeeHtml('data-flag-type="language"')
            ->assertSeeHtml('data-flag-code="en"');
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
