<?php

namespace Tests\Feature\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AlpinePrimitiveRenderingTest extends TestCase
{
    public function test_shared_alpine_primitives_render_with_registered_component_hooks(): void
    {
        $tooltip = Blade::render(<<<'BLADE'
            <x-ui.tooltip>
                <x-slot:trigger>More info</x-slot:trigger>
                <span>Tooltip body</span>
            </x-ui.tooltip>
        BLADE);
        $dropdown = Blade::render(<<<'BLADE'
            <x-ui.dropdown>
                <x-slot:button>
                    <button type="button">Open</button>
                </x-slot:button>

                <x-slot:menu>
                    <x-ui.dropdown.item>Profile</x-ui.dropdown.item>
                </x-slot:menu>
            </x-ui.dropdown>
        BLADE);
        $switch = Blade::render('<x-ui.switch name="notifications" label="Notifications" />');
        $accordion = Blade::render(<<<'BLADE'
            <x-ui.accordion>
                <x-ui.accordion.item trigger="Details">
                    <p>Body</p>
                </x-ui.accordion.item>
            </x-ui.accordion>
        BLADE);
        $popover = Blade::render(<<<'BLADE'
            <x-ui.popover>
                <x-ui.popover.trigger>Open</x-ui.popover.trigger>
                <x-ui.popover.overlay>Popover body</x-ui.popover.overlay>
            </x-ui.popover>
        BLADE);
        $popup = Blade::render('<x-ui.popup>Popup body</x-ui.popup>');
        $input = Blade::render('<x-ui.input name="password" copyable revealable />');
        $textarea = Blade::render('<x-ui.textarea name="summary" rows="4">Summary</x-ui.textarea>');

        $this->assertStringContainsString('x-data="tooltipComponent(', $tooltip);
        $this->assertStringContainsString('x-data="dropdownShell(', $dropdown);
        $this->assertStringContainsString('x-data="switchState(', $switch);
        $this->assertStringContainsString('x-data="accordionRoot()"', $accordion);
        $this->assertStringContainsString('x-data="accordionItem(', $accordion);
        $this->assertStringContainsString('x-data="popoverRoot()"', $popover);
        $this->assertStringContainsString('x-data="popupVisibility()"', $popup);
        $this->assertStringContainsString('x-data="inputCopyAction()"', $input);
        $this->assertStringContainsString('x-data="inputRevealToggle()"', $input);
        $this->assertStringContainsString('x-data="textareaAutosize(', $textarea);
    }
}
