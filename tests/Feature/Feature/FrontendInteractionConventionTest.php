<?php

namespace Tests\Feature\Feature;

use Tests\TestCase;

class FrontendInteractionConventionTest extends TestCase
{
    public function test_reusable_ui_primitives_delegate_alpine_state_to_registered_components(): void
    {
        $viewExpectations = [
            resource_path('views/livewire/search/global-search.blade.php') => 'x-data="globalSearchOverlay(',
            resource_path('views/components/ui/tooltip/index.blade.php') => 'x-data="tooltipComponent(',
            resource_path('views/components/ui/input/options/revealable.blade.php') => 'x-data="inputRevealToggle()"',
            resource_path('views/components/ui/input/options/copyable.blade.php') => 'x-data="inputCopyAction()"',
            resource_path('views/components/ui/dropdown/index.blade.php') => 'x-data="dropdownShell(',
            resource_path('views/components/ui/dropdown/submenu.blade.php') => 'x-data="dropdownSubmenu()"',
            resource_path('views/components/ui/dropdown/checkbox-or-radio.blade.php') => 'x-data="dropdownToggleable()"',
            resource_path('views/components/ui/popover/index.blade.php') => 'x-data="popoverRoot()"',
            resource_path('views/components/ui/popup.blade.php') => 'x-data="popupVisibility()"',
            resource_path('views/components/ui/switch/index.blade.php') => 'x-data="switchState(',
            resource_path('views/components/ui/accordion/index.blade.php') => 'x-data="accordionRoot()"',
            resource_path('views/components/ui/accordion/item.blade.php') => 'x-data="accordionItem(',
            resource_path('views/components/ui/textarea/index.blade.php') => 'x-data="textareaAutosize(',
        ];

        foreach ($viewExpectations as $viewPath => $expectedXData) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents, $viewPath);
            $this->assertStringContainsString($expectedXData, $contents, $viewPath);
            $this->assertStringNotContainsString('x-data="{', $contents, $viewPath);
            $this->assertStringNotContainsString('x-data="function(', $contents, $viewPath);
        }
    }

    public function test_frontend_entrypoint_registers_shared_alpine_state_modules(): void
    {
        $entrypoint = file_get_contents(base_path('resources/js/app.js'));
        $uiStateModule = file_get_contents(base_path('resources/js/components/ui-state.js'));

        $this->assertNotFalse($entrypoint);
        $this->assertNotFalse($uiStateModule);
        $this->assertStringContainsString("import registerUiStateComponents from './components/ui-state.js';", $entrypoint);
        $this->assertStringContainsString('registerUiStateComponents(Alpine);', $entrypoint);

        foreach ([
            "Alpine.data('globalSearchOverlay'",
            "Alpine.data('dropdownShell'",
            "Alpine.data('dropdownSubmenu'",
            "Alpine.data('dropdownToggleable'",
            "Alpine.data('tooltipComponent'",
            "Alpine.data('inputRevealToggle'",
            "Alpine.data('inputCopyAction'",
            "Alpine.data('popoverRoot'",
            "Alpine.data('popupVisibility'",
            "Alpine.data('switchState'",
            "Alpine.data('accordionRoot'",
            "Alpine.data('accordionItem'",
            "Alpine.data('textareaAutosize'",
        ] as $registration) {
            $this->assertStringContainsString($registration, $uiStateModule);
        }
    }
}
