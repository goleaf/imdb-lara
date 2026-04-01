<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->username ??= str($user->name)->slug()->value().fake()->numberBetween(10, 99);
            $user->role ??= UserRole::RegularUser;
            $user->status ??= UserStatus::Active;
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
}
