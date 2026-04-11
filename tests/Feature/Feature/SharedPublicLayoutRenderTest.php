<?php

namespace Tests\Feature\Feature;

use App\Actions\Seo\ResolvePageShellViewDataAction;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SharedPublicLayoutRenderTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_layouts_app_renders_the_shared_public_header_and_footer_for_livewire_style_shells(): void
    {
        Route::middleware('web')->get('/__layout-shell-preview', function (ResolvePageShellViewDataAction $resolvePageShellViewDataAction) {
            return view('layouts.app', [
                'shell' => $resolvePageShellViewDataAction->forLivewireLayout([
                    'pageTitle' => 'Search · Screenbase',
                    'pageDescription' => 'Search imported titles and people.',
                    'pageRobots' => 'noindex,follow',
                    'canonicalUrl' => url('/__layout-shell-preview'),
                    'shellVariant' => 'default',
                    'showFooter' => true,
                ]),
                'content' => '<div>Search Results</div>',
            ]);
        })->name('public.search.layout-test');

        $this->get('/__layout-shell-preview')
            ->assertOk()
            ->assertSee('Search The Global Catalog')
            ->assertSee('Browse by Theme')
            ->assertSee('Changes')
            ->assertSee('Search Results');
    }

    public function test_welcome_scaffold_uses_livewire_script_config_for_manual_bundling(): void
    {
        $contents = file_get_contents(resource_path('views/welcome.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('@livewireScriptConfig', $contents);
        $this->assertStringNotContainsString('@livewireScripts', $contents);
    }
}
