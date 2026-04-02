<?php

namespace App\Enums;

enum WatchState: string
{
    case Planned = 'planned';
    case Watching = 'watching';
    case Completed = 'completed';
    case Paused = 'paused';
    case Dropped = 'dropped';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Planned => 'bookmark',
            self::Watching => 'play-circle',
            self::Completed => 'check-circle',
            self::Paused => 'pause-circle',
            self::Dropped => 'x-circle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Watching => 'slate',
            self::Paused, self::Dropped => 'amber',
            self::Planned => 'neutral',
        };
    }
}
