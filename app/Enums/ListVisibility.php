<?php

namespace App\Enums;

enum ListVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Private => 'lock-closed',
            self::Unlisted => 'eye-slash',
            self::Public => 'globe-alt',
        };
    }
}
