<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LivewireLoadingStateConventionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_manage_list_uses_livewire_four_loading_shells_for_item_mutations_without_losing_targeted_search_loading(): void
    {
        $viewPaths = [
            resource_path('views/livewire/lists/manage-list.blade.php'),
        ];

        foreach ($viewPaths as $viewPath) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents, 'Expected to read '.$viewPath);
            $this->assertStringContainsString('[&:has(input[data-loading])_[data-slot=title-suggestion-loading]]:block', $contents, $viewPath);
            $this->assertStringContainsString('data-slot="title-suggestion-loading"', $contents, $viewPath);
            $this->assertStringContainsString('data-slot="title-suggestion-results"', $contents, $viewPath);
            $this->assertStringContainsString('has-data-loading:[&_[data-slot=manage-list-items-skeletons]]:grid', $contents, $viewPath);
            $this->assertStringContainsString('data-slot="manage-list-items-results"', $contents, $viewPath);
            $this->assertStringNotContainsString('wire:loading.delay.attr="data-loading"', $contents, $viewPath);
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

    public function test_profile_settings_panel_uses_validate_attributes_and_blur_synced_text_inputs(): void
    {
        $contents = file_get_contents(resource_path('views/components/account/⚡profile-settings-panel.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('use Livewire\\Attributes\\Validate;', $contents);
        $this->assertStringContainsString("#[Validate('required|string|max:255')]", $contents);
        $this->assertStringContainsString("#[Validate('nullable|string|max:1200')]", $contents);
        $this->assertStringContainsString("#[Validate('nullable|string|max:2048')]", $contents);
        $this->assertStringContainsString("#[Validate('required|in:public,private')]", $contents);
        $this->assertStringContainsString("#[Validate('required|boolean')]", $contents);
        $this->assertStringContainsString('wire:model.live.blur="name"', $contents);
        $this->assertStringContainsString('wire:model.live.blur="avatarPath"', $contents);
        $this->assertStringContainsString('wire:model.live.blur="bio"', $contents);
        $this->assertStringNotContainsString('wire:model.live="name"', $contents);
        $this->assertStringNotContainsString('wire:model.live="avatarPath"', $contents);
        $this->assertStringNotContainsString('wire:model.live="bio"', $contents);
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
        $this->assertStringContainsString('wire:target="setSort(\'{{ $sortOption[\'value\'] }}\')"', $contents);
        $this->assertStringContainsString('wire:target="toggleHelpful({{ $review->id }})"', $contents);
        $this->assertStringNotContainsString('wire:target="setSort"', $contents);
        $this->assertStringNotContainsString('wire:target="toggleHelpful"', $contents);
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

    public function test_search_results_keys_dynamic_filter_and_result_loops(): void
    {
        $contents = file_get_contents(resource_path('views/livewire/search/search-results.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('wire:target="clearTitleFilters"', $contents);
        $this->assertStringContainsString('wire:key="search-type-{{ $typeOption->value }}"', $contents);
        $this->assertStringContainsString('wire:key="search-genre-{{ $genreOption->id }}"', $contents);
        $this->assertStringContainsString('wire:key="search-status-{{ $statusOption[\'value\'] }}"', $contents);
        $this->assertStringContainsString('wire:key="search-title-{{ $title->id }}"', $contents);
        $this->assertStringContainsString('wire:key="search-person-{{ $person->id }}"', $contents);
        $this->assertStringContainsString('wire:key="search-interest-category-{{ $interestCategory->id }}"', $contents);
    }

    public function test_global_search_and_filmography_views_key_their_live_result_loops(): void
    {
        $globalSearchContents = file_get_contents(resource_path('views/livewire/search/global-search.blade.php'));
        $filmographyContents = file_get_contents(resource_path('views/livewire/people/filmography-panel.blade.php'));
        $filmographyClassContents = file_get_contents(app_path('Livewire/People/FilmographyPanel.php'));

        $this->assertNotFalse($globalSearchContents);
        $this->assertNotFalse($filmographyContents);
        $this->assertNotFalse($filmographyClassContents);
        $this->assertStringContainsString('wire:key="global-search-section-{{ $section[\'key\'] }}"', $globalSearchContents);
        $this->assertStringContainsString('wire:key="global-search-{{ $section[\'key\'] }}-{{ $suggestion->id }}"', $globalSearchContents);
        $this->assertStringContainsString('wire:key="filmography-group-{{ $group[\'key\'] }}"', $filmographyContents);
        $this->assertStringContainsString('wire:key="filmography-row-{{ $row[\'key\'] }}"', $filmographyContents);
        $this->assertStringContainsString("'key' => \$groupKey", $filmographyClassContents);
        $this->assertStringContainsString("'key' => \$groupKey.'-'.\$row['title']->getKey()", $filmographyClassContents);
    }

    public function test_filter_reset_and_inline_admin_actions_scope_loading_feedback(): void
    {
        $watchlistContents = file_get_contents(resource_path('views/livewire/account/watchlist-browser.blade.php'));
        $discoveryContents = file_get_contents(resource_path('views/livewire/search/discovery-filters.blade.php'));
        $professionEditorContents = file_get_contents(resource_path('views/livewire/admin/person-profession-editor.blade.php'));
        $manageListContents = file_get_contents(resource_path('views/livewire/lists/manage-list.blade.php'));

        $this->assertNotFalse($watchlistContents);
        $this->assertNotFalse($discoveryContents);
        $this->assertNotFalse($professionEditorContents);
        $this->assertNotFalse($manageListContents);
        $this->assertStringContainsString('wire:target="clearFilters"', $watchlistContents);
        $this->assertStringContainsString('wire:key="watchlist-visibility-{{ $visibilityOption[\'value\'] }}"', $watchlistContents);
        $this->assertStringContainsString('wire:target="clearFilters"', $discoveryContents);
        $this->assertStringContainsString('wire:key="discover-active-filter-', $discoveryContents);
        $this->assertStringContainsString('wire:target="save"', $professionEditorContents);
        $this->assertStringContainsString('wire:target="delete"', $professionEditorContents);
        $this->assertStringContainsString('wire:key="manage-list-visibility-{{ $visibilityOption[\'value\'] }}"', $manageListContents);
        $this->assertStringContainsString('wire:target="deleteList"', $manageListContents);
    }

    public function test_admin_moderation_cards_key_dynamic_status_options(): void
    {
        $viewExpectations = [
            resource_path('views/livewire/admin/report-moderation-card.blade.php') => 'wire:key="report-moderation-status-{{ $reportStatus->value }}"',
            resource_path('views/livewire/admin/review-moderation-card.blade.php') => 'wire:key="review-moderation-status-{{ $reviewStatus->value }}"',
            resource_path('views/livewire/admin/contribution-moderation-card.blade.php') => 'wire:key="contribution-moderation-status-{{ $contributionStatus->value }}"',
        ];

        foreach ($viewExpectations as $viewPath => $expectedString) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString($expectedString, $contents, $viewPath);
        }
    }

    public function test_search_shells_use_lazy_islands_with_placeholder_fallbacks(): void
    {
        $viewExpectations = [
            resource_path('views/livewire/search/discovery-filters.blade.php') => "@island(name: 'discover-results-page', lazy: true)",
            resource_path('views/livewire/search/search-results.blade.php') => "@island(name: 'search-results-page', lazy: true)",
        ];

        foreach ($viewExpectations as $viewPath => $lazyIslandDeclaration) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString($lazyIslandDeclaration, $contents, $viewPath);
            $this->assertStringContainsString('@placeholder', $contents, $viewPath);
            $this->assertStringContainsString('@endplaceholder', $contents, $viewPath);
        }
    }

    public function test_global_search_component_uses_url_synced_queries_and_computed_view_data(): void
    {
        $classPath = app_path('Livewire/Search/GlobalSearch.php');
        $contents = file_get_contents($classPath);

        $this->assertNotFalse($contents, $classPath);
        $this->assertStringContainsString('#[Url(as: \'q\')]', $contents, $classPath);
        $this->assertStringContainsString('#[Locked]', $contents, $classPath);
        $this->assertStringContainsString('public function viewData(): array', $contents, $classPath);
        $this->assertStringContainsString("return view('livewire.search.global-search', \$this->viewData);", $contents, $classPath);
        $this->assertStringNotContainsString("\$this->query = trim((string) request('q'));", $contents, $classPath);
    }

    public function test_select_and_combobox_shells_rely_on_livewire_four_data_loading_without_view_level_wire_loading_directives(): void
    {
        $rootViewExpectations = [
            resource_path('views/components/ui/select/index.blade.php'),
            resource_path('views/components/ui/combobox/index.blade.php'),
        ];

        foreach ($rootViewExpectations as $viewPath) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents, $viewPath);
            $this->assertStringContainsString('data-loading:[&_[data-slot=options-list-loading]]:flex', $contents, $viewPath);
            $this->assertStringContainsString('data-loading:[&_[data-slot=option]]:hidden', $contents, $viewPath);
        }

        $optionsViewExpectations = [
            resource_path('views/components/ui/select/options.blade.php'),
            resource_path('views/components/ui/combobox/options.blade.php'),
        ];

        foreach ($optionsViewExpectations as $viewPath) {
            $contents = file_get_contents($viewPath);

            $this->assertNotFalse($contents, $viewPath);
            $this->assertStringContainsString('data-slot="options-list"', $contents, $viewPath);
            $this->assertStringNotContainsString('wire:loading.attr="data-loading"', $contents, $viewPath);
        }
    }

    public function test_heavier_livewire_components_shift_view_assembly_into_computed_properties(): void
    {
        $classExpectations = [
            app_path('Livewire/Account/WatchlistBrowser.php') => [
                '#[Computed]',
                'public function viewData(): array',
                "return view('livewire.account.watchlist-browser', \$this->viewData);",
            ],
            app_path('Livewire/People/FilmographyPanel.php') => [
                '#[Computed]',
                '#[Locked]',
                'public function viewData(): array',
                "return view('livewire.people.filmography-panel', \$this->viewData);",
            ],
            app_path('Livewire/Lists/ManageList.php') => [
                '#[Computed]',
                '#[Locked]',
                'public function viewData(): array',
                "return view('livewire.lists.manage-list', \$this->viewData);",
            ],
        ];

        foreach ($classExpectations as $classPath => $expectedStrings) {
            $contents = file_get_contents($classPath);

            $this->assertNotFalse($contents);

            foreach ($expectedStrings as $expectedString) {
                $this->assertStringContainsString($expectedString, $contents, $classPath);
            }
        }
    }

    public function test_review_and_title_interaction_components_lock_server_owned_models(): void
    {
        $classExpectations = [
            app_path('Livewire/Reviews/TitleReviewList.php') => [
                '#[Locked]',
                'public Title $title;',
                '#[Computed]',
                'public function viewData(): array',
            ],
            app_path('Livewire/Titles/RatingPanel.php') => [
                '#[Locked]',
                'public Title $title;',
                '#[Computed]',
                'public function viewData(): array',
            ],
            app_path('Livewire/Titles/ReviewComposer.php') => [
                '#[Locked]',
                'public Title $title;',
                'public ?Review $review = null;',
                '#[Computed]',
                'public function viewData(): array',
            ],
        ];

        foreach ($classExpectations as $classPath => $expectedStrings) {
            $contents = file_get_contents($classPath);

            $this->assertNotFalse($contents);

            foreach ($expectedStrings as $expectedString) {
                $this->assertStringContainsString($expectedString, $contents, $classPath);
            }
        }
    }

    public function test_watchlist_and_watch_state_panels_use_computed_view_data_and_livewire_four_text_updates(): void
    {
        $watchlistClass = file_get_contents(app_path('Livewire/Titles/WatchlistToggle.php'));
        $watchlistView = file_get_contents(resource_path('views/livewire/titles/watchlist-toggle.blade.php'));
        $watchStateClass = file_get_contents(app_path('Livewire/Titles/WatchStatePanel.php'));
        $watchStateView = file_get_contents(resource_path('views/livewire/titles/watch-state-panel.blade.php'));

        $this->assertNotFalse($watchlistClass);
        $this->assertNotFalse($watchlistView);
        $this->assertNotFalse($watchStateClass);
        $this->assertNotFalse($watchStateView);

        $this->assertStringContainsString('#[Computed]', $watchlistClass);
        $this->assertStringContainsString('public function viewData(): array', $watchlistClass);
        $this->assertStringContainsString("return view('livewire.titles.watchlist-toggle', \$this->viewData);", $watchlistClass);
        $this->assertStringContainsString('x-on:click="$wire.inWatchlist = ! $wire.inWatchlist"', $watchlistView);
        $this->assertStringContainsString('wire:text="inWatchlist ? \'Saved to watchlist\' : \'Save to watchlist\'"', $watchlistView);
        $this->assertStringContainsString('not-data-loading:opacity-100', $watchlistView);

        $this->assertStringContainsString('#[Computed]', $watchStateClass);
        $this->assertStringContainsString('public bool $isCompleted = false;', $watchStateClass);
        $this->assertStringContainsString('public function viewData(): array', $watchStateClass);
        $this->assertStringContainsString("return view('livewire.titles.watch-state-panel', \$this->viewData);", $watchStateClass);
        $this->assertStringContainsString('x-on:click="$wire.isCompleted = ! $wire.isCompleted"', $watchStateView);
        $this->assertStringContainsString('wire:text="isCompleted ? \'Mark unwatched\' : \'Mark watched\'"', $watchStateView);
        $this->assertStringContainsString('wire:show="statusMessage"', $watchStateView);
        $this->assertStringContainsString('wire:text="statusMessage"', $watchStateView);
    }
}
