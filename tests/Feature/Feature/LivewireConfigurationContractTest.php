<?php

namespace Tests\Feature\Feature;

use Tests\TestCase;

class LivewireConfigurationContractTest extends TestCase
{
    public function test_livewire_configuration_is_published_for_the_current_app_layout_and_manual_asset_bootstrap(): void
    {
        $this->assertFileExists(config_path('livewire.php'));
        $this->assertSame('layouts.app', config('livewire.component_layout'));
        $this->assertFalse(config('livewire.inject_assets'));
        $this->assertFalse(config('livewire.legacy_model_binding'));
        $this->assertSame('tailwind', config('livewire.pagination_theme'));
        $this->assertSame('sfc', config('livewire.make_command.type'));
        $this->assertTrue(config('livewire.make_command.emoji'));
        $this->assertContains(resource_path('views/components'), config('livewire.component_locations'));
        $this->assertContains(resource_path('views/livewire'), config('livewire.component_locations'));
    }
}
