<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending moderation',
            self::Published => 'Published',
            self::Rejected => 'Needs revision',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Pending => 'amber',
            self::Published => 'green',
            self::Rejected => 'red',
        };
    }
}
