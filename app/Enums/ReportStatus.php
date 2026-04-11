<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function priority(): int
    {
        return match ($this) {
            self::Open => 0,
            self::Investigating => 1,
            self::Resolved => 2,
            self::Dismissed => 3,
        };
    }
}
