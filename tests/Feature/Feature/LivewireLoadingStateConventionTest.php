<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LivewireLoadingStateConventionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_interactive_livewire_views_use_data_loading_wrappers(): void
    {
        $viewPaths = [
            resource_path('views/livewire/account/watchlist-browser.blade.php'),
            resource_path('views/livewire/catalog/interest-category-browser.blade.php'),
            resource_path('views/livewire/catalog/people-browser.blade.php'),
            resource_path('views/livewire/lists/manage-list.blade.php'),
            resource_path('views/livewire/people/filmography-panel.blade.php'),
            resource_path('views/livewire/search/discovery-filters.blade.php'),
            resource_path('views/livewire/search/search-results.blade.php'),
        ];

        foreach ($viewPaths as $viewPath) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents, 'Expected to read '.$viewPath);
            $this->assertMatchesRegularExpression('/wire:loading(?:\\.delay)?\\.attr="data-loading"/', $contents, $viewPath);
            $this->assertStringNotContainsString('wire:loading.remove', $contents, $viewPath);
        }
    }

    public function test_people_page_lazy_loads_the_filmography_panel(): void
    {
        $contents = file_get_contents(resource_path('views/people/show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertMatchesRegularExpression('/<livewire:people\\.filmography-panel[\\s\\S]*?\\slazy\\s*\\/>/', $contents);
    }

    public function test_title_review_list_uses_tailwind_has_data_loading_variant(): void
    {
        $contents = file_get_contents(resource_path('views/livewire/reviews/title-review-list.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('has-data-loading:[&_[data-slot=review-list-loading]]:inline-flex', $contents);
        $this->assertStringNotContainsString('has-[button[data-loading]]', $contents);
    }

    public function test_public_lists_page_uses_data_loading_conventions(): void
    {
        $contents = file_get_contents(resource_path('views/lists/index.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('wire:loading.delay.attr="data-loading"', $contents);
        $this->assertStringContainsString('not-data-loading:hidden', $contents);
        $this->assertStringContainsString('in-data-loading:hidden', $contents);
        $this->assertStringNotContainsString('wire:loading.remove', $contents);
    }
}
