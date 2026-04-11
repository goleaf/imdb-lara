<?php

namespace App\Enums;

enum ContributionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function priority(): int
    {
        return match ($this) {
            self::Submitted => 0,
            self::Approved => 1,
            self::Rejected => 2,
        };
    }
}
