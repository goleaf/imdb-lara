<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NativeSelectComponentTest extends TestCase
{
    public function test_native_select_component_renders_the_standard_control_shell(): void
    {
        $markup = Blade::render(
            '<x-ui.native-select name="status"><option value="pending">Pending</option></x-ui.native-select>',
        );

        $this->assertStringContainsString('<select', $markup);
        $this->assertStringContainsString('name="status"', $markup);
        $this->assertStringContainsString('data-slot="control"', $markup);
        $this->assertStringContainsString('rounded-box', $markup);
    }

    public function test_native_select_component_can_render_an_invalid_state(): void
    {
        $markup = Blade::render(
            '<x-ui.native-select name="status" :invalid="true"><option value="pending">Pending</option></x-ui.native-select>',
        );

        $this->assertStringContainsString('aria-invalid="true"', $markup);
        $this->assertStringContainsString('border-red-600/30', $markup);
    }
}
