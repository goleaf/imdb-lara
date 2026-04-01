<?php

namespace App\Enums;

enum CompanyKind: string
{
    case Production = 'production';
    case Distributor = 'distributor';
    case Network = 'network';
    case Streamer = 'streamer';
    case Studio = 'studio';
}
