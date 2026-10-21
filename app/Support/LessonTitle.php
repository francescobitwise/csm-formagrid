<?php

declare(strict_types=1);

namespace App\Support;

final class LessonTitle
{
    /**
     * Deriva un titolo lezione dal nome file (senza estensione).
     */
    public static function fromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = html_entity_decode($base, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $base = preg_replace('/[_\-]+/u', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/u', ' ', $base) ?? $base;
        $base = trim($base);

        if (mb_strlen($base) < 2) {
            $base = 'Lezione';
        }

        return mb_substr($base, 0, 200);
    }
}
