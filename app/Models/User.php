<?php

namespace App\Models;

use App\Enums\ListVisibility;
use App\Enums\ProfileVisibility;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'bio',
        'avatar_path',
        'role',
        'status',
        'profile_visibility',
        'show_ratings_on_profile',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'profile_visibility' => ProfileVisibility::class,
            'show_ratings_on_profile' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->username ??= str($user->name)->slug()->value().fake()->numberBetween(10, 99);
            $user->role ??= UserRole::RegularUser;
            $user->status ??= UserStatus::Active;
            $user->profile_visibility ??= ProfileVisibility::Public;
            $user->show_ratings_on_profile ??= true;
        });
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function reviewedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'moderator_id');
    }

    public function lists(): HasMany
    {
        return $this->hasMany(UserList::class);
    }

    public function customLists(): HasMany
    {
        return $this->hasMany(UserList::class)->where('is_watchlist', false);
    }

    public function publicLists(): HasMany
    {
        return $this->hasMany(UserList::class)
            ->where('is_watchlist', false)
            ->where('visibility', ListVisibility::Public->value);
    }

    public function publicWatchlist(): HasOne
    {
        return $this->hasOne(UserList::class)
            ->where('is_watchlist', true)
            ->where('visibility', ListVisibility::Public->value);
    }

    public function watchlist(): HasOne
    {
        return $this->hasOne(UserList::class)->where('is_watchlist', true);
    }

    public function watchlistEntries(): HasManyThrough
    {
        return $this->hasManyThrough(
            ListItem::class,
            UserList::class,
            'user_id',
            'user_list_id',
            'id',
            'id',
        )->where('user_lists.is_watchlist', true);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdministrative() ?? false;
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->role?->canAccessAdminPanel() ?? false;
    }

    public function canManageCatalog(): bool
    {
        return $this->role?->canManageCatalog() ?? false;
    }

    public function canModerateContent(): bool
    {
        return $this->role?->canModerateContent() ?? false;
    }

    public function canSubmitContributions(): bool
    {
        return $this->role?->canSubmitContributions() ?? false;
    }

    public function canReviewContributions(): bool
    {
        return $this->role?->canReviewContributions() ?? false;
    }

    public function canManageMedia(): bool
    {
        return $this->role?->canManageMedia() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isProfilePublic(): bool
    {
        if ($this->profile_visibility instanceof ProfileVisibility) {
            return $this->profile_visibility === ProfileVisibility::Public;
        }

        return ProfileVisibility::tryFrom((string) $this->profile_visibility) === ProfileVisibility::Public;
    }

    public function showsRatingsOnProfile(): bool
    {
        return $this->show_ratings_on_profile;
    }

    public function hasVisibleProfileContent(): bool
    {
        if (! $this->isProfilePublic()) {
            return false;
        }

        if ($this->customLists()->where('visibility', ListVisibility::Public->value)->exists()) {
            return true;
        }

        if ($this->watchlist()->where('visibility', ListVisibility::Public->value)->exists()) {
            return true;
        }

        if ($this->reviews()->published()->exists()) {
            return true;
        }

        return $this->showsRatingsOnProfile() && $this->ratings()->exists();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (blank($this->avatar_path)) {
            return null;
        }

        if (filter_var($this->avatar_path, FILTER_VALIDATE_URL)) {
            return $this->avatar_path;
        }

        return Storage::url($this->avatar_path);
    }
}
