<?php

namespace App;

enum UserRole: string
{
    case RegularUser = 'member';
    case Contributor = 'contributor';
    case Moderator = 'moderator';
    case Editor = 'editor';
    case Admin = 'admin';
    case SuperAdmin = 'superadmin';

    public function isAdministrative(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => true,
            default => false,
        };
    }

    public function canAccessAdminPanel(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Editor, self::Moderator => true,
            default => false,
        };
    }

    public function canManageCatalog(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Editor => true,
            default => false,
        };
    }

    public function canModerateContent(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Moderator => true,
            default => false,
        };
    }

    public function canSubmitContributions(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Editor, self::Moderator, self::Contributor => true,
            default => false,
        };
    }

    public function canReviewContributions(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Editor => true,
            default => false,
        };
    }

    public function canManageMedia(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Editor => true,
            default => false,
        };
    }
}
