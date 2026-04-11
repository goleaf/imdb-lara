<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class FlagComponentTest extends TestCase
{
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

    public function test_flag_component_scopes_inline_svg_ids_per_instance(): void
    {
        $markup = Blade::render(
            '<x-ui.flag type="country" code="us" class="size-4" /><x-ui.flag type="country" code="us" class="size-4" />',
        );

        $this->assertStringContainsString('data-slot="flag"', $markup);
        $this->assertSame(2, substr_count($markup, 'data-flag-code="us"'));

        libxml_use_internal_errors(true);

        $document = new \DOMDocument;
        $document->loadHTML($markup);

        $ids = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element->hasAttribute('id')) {
                $ids[] = $element->getAttribute('id');
            }
        }

        $this->assertNotEmpty($ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
    }
}
