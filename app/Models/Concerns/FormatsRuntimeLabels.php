<?php

namespace App\Models\Concerns;

trait FormatsRuntimeLabels
{
    public static function formatMinutesLabel(?int $minutes): ?string
    {
        if ($minutes === null || $minutes < 1) {
            return null;
        }

        if ($minutes <= 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d min (%dh %dmin)', $minutes, $hours, $remainingMinutes);
    }

    public static function formatSecondsAsMinutesLabel(?int $seconds): ?string
    {
        if ($seconds === null || $seconds < 1) {
            return null;
        }

        return static::formatMinutesLabel(max(1, (int) ceil($seconds / 60)));
    }
}
