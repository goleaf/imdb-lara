<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Abuse = 'abuse';
    case Spoiler = 'spoiler';
    case Harassment = 'harassment';
    case Inaccurate = 'inaccurate';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Spoiler => 'exclamation-triangle',
            self::Spam => 'no-symbol',
            self::Abuse, self::Harassment => 'shield-exclamation',
            self::Inaccurate => 'information-circle',
        };
    }
}
