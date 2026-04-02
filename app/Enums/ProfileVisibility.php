<?php

namespace App\Enums;

enum ProfileVisibility: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Public => 'globe-alt',
            self::Private => 'lock-closed',
        };
    }
}
