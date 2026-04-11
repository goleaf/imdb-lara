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

    public function test_account_pages_defer_heavier_livewire_components(): void
    {
        $watchlistPage = file_get_contents(resource_path('views/account/watchlist.blade.php'));
        $listsIndexPage = file_get_contents(resource_path('views/account/lists/index.blade.php'));
        $manageListPage = file_get_contents(resource_path('views/account/lists/show.blade.php'));
        $settingsPage = file_get_contents(resource_path('views/account/settings.blade.php'));

        $this->assertNotFalse($watchlistPage);
        $this->assertNotFalse($listsIndexPage);
        $this->assertNotFalse($manageListPage);
        $this->assertNotFalse($settingsPage);
        $this->assertMatchesRegularExpression('/<livewire:account\\.watchlist-browser[\\s\\S]*?\\sdefer\\s*\\/>/', $watchlistPage);
        $this->assertMatchesRegularExpression('/<livewire:lists\\.create-list-form[\\s\\S]*?\\sdefer\\s*\\/>/', $listsIndexPage);
        $this->assertMatchesRegularExpression('/<livewire:lists\\.manage-list[\\s\\S]*?\\sdefer\\s*\\/>/', $manageListPage);
        $this->assertMatchesRegularExpression('/<livewire:account\\.profile-settings-panel[\\s\\S]*?\\sdefer\\s*\\/>/', $settingsPage);
    }

    public function test_deferred_account_components_define_placeholder_methods(): void
    {
        $classPaths = [
            app_path('Livewire/Account/WatchlistBrowser.php'),
            app_path('Livewire/Lists/CreateListForm.php'),
            app_path('Livewire/Lists/ManageList.php'),
        ];

        foreach ($classPaths as $classPath) {
            $contents = file_get_contents($classPath);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('function placeholder()', $contents, $classPath);
        }
    }

    public function test_profile_settings_panel_defines_a_lazy_placeholder_and_scoped_submit_loading_state(): void
    {
        $contents = file_get_contents(resource_path('views/components/account/⚡profile-settings-panel.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('@placeholder', $contents);
        $this->assertStringContainsString('wire:target="save"', $contents);
    }

    public function test_embedded_livewire_actions_scope_button_loading_states(): void
    {
        $reviewReportContents = file_get_contents(resource_path('views/livewire/reviews/report-review-form.blade.php'));
        $listReportContents = file_get_contents(resource_path('views/livewire/lists/report-list-form.blade.php'));
        $customListPickerContents = file_get_contents(resource_path('views/livewire/titles/custom-list-picker.blade.php'));

        $this->assertNotFalse($reviewReportContents);
        $this->assertNotFalse($listReportContents);
        $this->assertNotFalse($customListPickerContents);
        $this->assertStringContainsString('form="review-report-form-', $reviewReportContents);
        $this->assertStringContainsString('wire:target="save"', $reviewReportContents);
        $this->assertStringContainsString('form="list-report-form-', $listReportContents);
        $this->assertStringContainsString('wire:target="save"', $listReportContents);
        $this->assertStringContainsString('wire:click="createList"', $customListPickerContents);
        $this->assertStringContainsString('wire:target="createList"', $customListPickerContents);
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

    public function test_watchlist_browser_limits_loading_state_to_results_interactions(): void
    {
        $contents = file_get_contents(resource_path('views/livewire/account/watchlist-browser.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(
            'wire:target="genre,sort,state,type,year,clearFilters,toggleWatched,removeFromWatchlist,gotoPage,nextPage,previousPage,setPage"',
            $contents,
        );
    }
}
