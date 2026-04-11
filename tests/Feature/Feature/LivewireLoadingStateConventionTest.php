<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LivewireLoadingStateConventionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_manage_list_keeps_scoped_loading_wrappers_for_multiple_independent_regions(): void
    {
        $viewPaths = [
            resource_path('views/livewire/lists/manage-list.blade.php'),
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

    public function test_heavier_embedded_livewire_views_are_wrapped_in_islands(): void
    {
        $viewExpectations = [
            resource_path('views/livewire/account/watchlist-browser.blade.php') => 'data-slot="watchlist-browser-island"',
            resource_path('views/livewire/lists/manage-list.blade.php') => 'data-slot="manage-list-island"',
            resource_path('views/livewire/people/filmography-panel.blade.php') => 'data-slot="person-filmography-island"',
        ];

        foreach ($viewExpectations as $viewPath => $islandSlot) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('@island(name:', $contents, $viewPath);
            $this->assertStringContainsString($islandSlot, $contents, $viewPath);
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
        $this->assertStringContainsString('has-data-loading:[&_[data-slot=public-lists-skeletons]]:grid', $contents);
        $this->assertStringContainsString('data-slot="public-lists-results"', $contents);
        $this->assertStringContainsString('data-slot="public-lists-grid"', $contents);
        $this->assertStringNotContainsString('wire:loading.delay.attr="data-loading"', $contents);
    }

    public function test_browse_surfaces_use_livewire_four_has_data_loading_shells(): void
    {
        $viewExpectations = [
            resource_path('views/livewire/account/watchlist-browser.blade.php') => [
                'has-data-loading:[&_[data-slot=watchlist-browser-skeletons]]:grid',
                'data-slot="watchlist-browser-results"',
            ],
            resource_path('views/livewire/catalog/people-browser.blade.php') => [
                'has-data-loading:[&_[data-slot=people-browser-skeletons]]:grid',
                'data-slot="people-browser-results"',
            ],
            resource_path('views/livewire/catalog/interest-category-browser.blade.php') => [
                'has-data-loading:[&_[data-slot=interest-category-browser-skeletons]]:grid',
                'data-slot="interest-category-browser-results"',
            ],
            resource_path('views/livewire/people/filmography-panel.blade.php') => [
                'has-data-loading:[&_[data-slot=person-filmography-skeletons]]:block',
                'data-slot="person-filmography-results"',
            ],
            resource_path('views/livewire/search/discovery-filters.blade.php') => [
                'has-data-loading:[&_[data-slot=discover-skeletons]]:grid',
                'data-slot="discover-results"',
            ],
            resource_path('views/livewire/search/search-results.blade.php') => [
                'has-data-loading:[&_[data-slot=search-results-loading]]:block',
                'data-slot="search-results-results"',
            ],
        ];

        foreach ($viewExpectations as $viewPath => $expectedStrings) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents);

            foreach ($expectedStrings as $expectedString) {
                $this->assertStringContainsString($expectedString, $contents, $viewPath);
            }

            $this->assertStringNotContainsString('wire:loading.delay.attr="data-loading"', $contents, $viewPath);
        }
    }
}
