<?php

declare(strict_types=1);

namespace App\Support;

final class DurationFormat
{
    /** Converte "8:03" o "12:45" in secondi. Vuoto → null. */
    public static function mmssToSeconds(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parts = explode(':', $value);
        if (count($parts) !== 2) {
            return null;
        }

        $minutes = (int) $parts[0];
        $seconds = (int) $parts[1];

        if ($seconds < 0 || $seconds > 59 || $minutes < 0) {
            return null;
        }

        return $minutes * 60 + $seconds;
    }

    /** Secondi → "8:03" (senza ore; per durate lunghe: "125:59"). */
    public static function secondsToMmss(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '';
        }

        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return sprintf('%d:%02d', $m, $s);
    }
}
