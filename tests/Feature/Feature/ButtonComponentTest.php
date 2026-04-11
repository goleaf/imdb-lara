<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ButtonComponentTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_button_component_uses_data_loading_selectors_for_livewire_feedback(): void
    {
        $markup = Blade::render(
            '<x-ui.button wire:click="save" wire:target="save" icon="star">Save rating</x-ui.button>',
        );

        $this->assertStringContainsString('wire:target="save"', $markup);
        $this->assertStringNotContainsString('wire:loading.attr="data-loading"', $markup);
        $this->assertStringContainsString('not-in-data-loading:hidden', $markup);
        $this->assertStringContainsString('in-data-loading:opacity-0', $markup);
    }

    public function test_dropdown_item_component_uses_livewire_data_loading_selectors_without_wire_loading_directives(): void
    {
        $markup = Blade::render(
            '<x-ui.dropdown.item wire:click="archive" wire:target="archive">Archive</x-ui.dropdown.item>',
        );

        $this->assertStringContainsString('wire:target="archive"', $markup);
        $this->assertStringNotContainsString('wire:loading.attr="data-loading"', $markup);
        $this->assertStringContainsString('data-loading:opacity-55', $markup);
    }

    public function test_button_component_can_render_a_static_loading_state(): void
    {
        $markup = Blade::render(
            '<x-ui.button :loading="true" icon="star">Save rating</x-ui.button>',
        );

        $this->assertStringContainsString('data-loading="true"', $markup);
        $this->assertStringContainsString('not-in-data-loading:hidden', $markup);
    }
}
