<?php

namespace App\Enums;

enum TitleType: string
{
    case Movie = 'movie';
    case Series = 'series';
    case MiniSeries = 'mini-series';
    case Documentary = 'documentary';
    case Special = 'special';
    case Short = 'short';
    case Episode = 'episode';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Series, self::MiniSeries => 'tv',
            self::Documentary => 'camera',
            self::Special => 'sparkles',
            self::Episode => 'rectangle-stack',
            default => 'film',
        };
    }
}
