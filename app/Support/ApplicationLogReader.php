<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Lettura sicura (solo storage/logs/laravel*.log) delle ultime righe del log applicativo.
 */
final class ApplicationLogReader
{
    public const DEFAULT_LINES = 500;

    public const MAX_LINES = 2000;

    /**
     * @return list<string> Nomi file (basename), più recenti per primi
     */
    public function listBasenames(): array
    {
        $paths = glob(storage_path('logs/laravel*.log')) ?: [];

        usort($paths, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return array_values(array_map('basename', $paths));
    }

    /**
     * @return array{
     *     file: string,
     *     path: string,
     *     file_size: int|null,
     *     file_modified_at: int|null,
     *     lines: list<string>,
     *     matched_count: int,
     *     truncated: bool,
     * }
     */
    public function read(
        ?string $requestedBasename,
        int $lines = self::DEFAULT_LINES,
        ?string $search = null,
        ?string $level = null,
    ): array {
        if ($requestedBasename !== null && $requestedBasename !== '' && ! $this->isAllowedBasename($requestedBasename)) {
            return [
                'file' => 'laravel.log',
                'path' => '',
                'file_size' => null,
                'file_modified_at' => null,
                'lines' => [],
                'matched_count' => 0,
                'truncated' => false,
            ];
        }

        $basename = $this->resolveBasename($requestedBasename);
        $path = $this->pathForBasename($basename);
        $lines = max(50, min(self::MAX_LINES, $lines));

        if ($path === null || ! is_readable($path)) {
            return [
                'file' => $basename,
                'path' => $path ?? '',
                'file_size' => null,
                'file_modified_at' => null,
                'lines' => [],
                'matched_count' => 0,
                'truncated' => false,
            ];
        }

        $rawLines = $this->tailLines($path, $lines * 4);
        $filtered = $this->filterLines($rawLines, $search, $level);
        $truncated = count($filtered) > $lines;
        $displayLines = array_slice($filtered, -$lines);

        return [
            'file' => $basename,
            'path' => $path,
            'file_size' => filesize($path) ?: null,
            'file_modified_at' => filemtime($path) ?: null,
            'lines' => array_values($displayLines),
            'matched_count' => count($displayLines),
            'truncated' => $truncated,
        ];
    }

    private function resolveBasename(?string $requestedBasename): string
    {
        $available = $this->listBasenames();

        if ($requestedBasename !== null && $requestedBasename !== '') {
            if (! $this->isAllowedBasename($requestedBasename)) {
                return 'laravel.log';
            }

            return $requestedBasename;
        }

        return $available[0] ?? 'laravel.log';
    }

    private function pathForBasename(string $basename): ?string
    {
        if (! $this->isAllowedBasename($basename)) {
            return null;
        }

        $path = storage_path('logs/'.$basename);

        return is_file($path) ? $path : null;
    }

    private function isAllowedBasename(string $basename): bool
    {
        return preg_match('/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/', $basename) === 1;
    }

    /**
     * @return list<string>
     */
    private function tailLines(string $path, int $lines): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        try {
            $fileSize = filesize($path);
            if ($fileSize === false || $fileSize === 0) {
                return [];
            }

            $chunkSize = 8192;
            $buffer = '';
            $position = $fileSize;
            $collected = [];

            while ($position > 0 && count($collected) < $lines) {
                $readSize = (int) min($chunkSize, $position);
                $position -= $readSize;
                fseek($handle, $position);
                $chunk = fread($handle, $readSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                $buffer = $chunk.$buffer;
                while (($newlinePos = strrpos($buffer, "\n")) !== false && count($collected) < $lines) {
                    $line = substr($buffer, $newlinePos + 1);
                    $buffer = substr($buffer, 0, $newlinePos);
                    if ($line !== '') {
                        array_unshift($collected, rtrim($line, "\r"));
                    }
                }
            }

            if ($buffer !== '' && count($collected) < $lines) {
                array_unshift($collected, rtrim($buffer, "\r"));
            }

            return $collected;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function filterLines(array $lines, ?string $search, ?string $level): array
    {
        $search = trim((string) $search);
        $level = strtolower(trim((string) $level));

        return array_values(array_filter($lines, function (string $line) use ($search, $level): bool {
            if ($level !== '' && ! $this->lineMatchesLevel($line, $level)) {
                return false;
            }

            if ($search !== '' && ! Str::contains(strtolower($line), strtolower($search))) {
                return false;
            }

            return true;
        }));
    }

    private function lineMatchesLevel(string $line, string $level): bool
    {
        return match ($level) {
            'emergency' => str_contains($line, '.EMERGENCY:'),
            'alert' => str_contains($line, '.ALERT:'),
            'critical' => str_contains($line, '.CRITICAL:'),
            'error' => str_contains($line, '.ERROR:'),
            'warning' => str_contains($line, '.WARNING:'),
            'notice' => str_contains($line, '.NOTICE:'),
            'info' => str_contains($line, '.INFO:'),
            'debug' => str_contains($line, '.DEBUG:'),
            default => true,
        };
    }
}
