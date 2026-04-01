<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserCanModerate;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\ListItem;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Observers\ListItemObserver;
use App\Observers\RatingObserver;
use App\Observers\ReviewObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(static fn () => route('public.discover'));

        Gate::before(static fn (User $user, string $ability): ?bool => $user->isSuperAdmin() ? true : null);

        Gate::define('access-admin-area', static fn (User $user): bool => $user->canAccessAdminPanel());
        Gate::define('manage-catalog', static fn (User $user): bool => $user->canManageCatalog());
        Gate::define('moderate-content', static fn (User $user): bool => $user->canModerateContent());
        Gate::define('submit-contribution', static fn (User $user): bool => $user->canSubmitContributions());
        Gate::define('review-contribution', static fn (User $user): bool => $user->canReviewContributions());
        Gate::define('manage-media', static fn (User $user): bool => $user->canManageMedia());

        Rating::observe(RatingObserver::class);
        Review::observe(ReviewObserver::class);
        ListItem::observe(ListItemObserver::class);

        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
            EnsureUserIsAdmin::class,
            EnsureUserCanModerate::class,
        ]);
    }
}
