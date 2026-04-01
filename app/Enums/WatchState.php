<?php

namespace App\Enums;

enum WatchState: string
{
    case Planned = 'planned';
    case Watching = 'watching';
    case Completed = 'completed';
    case Paused = 'paused';
    case Dropped = 'dropped';
}
