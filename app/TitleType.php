<?php

namespace App;

enum TitleType: string
{
    case Movie = 'movie';
    case Series = 'series';
    case Documentary = 'documentary';
    case Special = 'special';
    case Short = 'short';
    case Episode = 'episode';
}
