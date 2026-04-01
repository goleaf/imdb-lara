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
}
