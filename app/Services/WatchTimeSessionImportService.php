<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;

final class WatchTimeSessionImportService
{
    /**
     * @param  array<string, string>  $lessonIdsByNormalizedTitle
     * @return array{rows: list<array{started_at: Carbon, ended_at: Carbon, video_seconds: int, scorm_seconds: int, lesson_id: ?string}>, errors: list<string>}
     */
    public function parse(string $path, array $lessonIdsByNormalizedTitle): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Impossibile leggere il file.']];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['CSV vuoto.']];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header)) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['CSV vuoto.']];
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        }

        $map = $this->normalizeHeader($header);
        $missing = array_values(array_filter(['inizio', 'fine'], fn (string $key) => ! isset($map[$key])));
        if ($missing !== []) {
            fclose($handle);

            return [
                'rows' => [],
                'errors' => ['Colonne obbligatorie mancanti: '.implode(', ', $missing).'. Usa la virgola o il punto e virgola come separatore.'],
            ];
        }

        if (! isset($map['minuti_video']) && ! isset($map['minuti_scorm'])) {
            fclose($handle);

            return [
                'rows' => [],
                'errors' => ['Aggiungi almeno una colonna tra minuti_video e minuti_scorm.'],
            ];
        }

        $rows = [];
        $errors = [];
        $line = 1;
        $processed = 0;
        $maxRows = 2000;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $processed++;
            if ($processed > $maxRows) {
                $errors[] = "Import interrotto: superato il limite di {$maxRows} righe.";
                break;
            }

            $parsed = $this->parseRow($row, $map, $lessonIdsByNormalizedTitle, $line);
            if (isset($parsed['error'])) {
                $errors[] = $parsed['error'];
                if (count($errors) >= 50) {
                    $errors[] = 'Altri errori omessi.';
                    break;
                }

                continue;
            }

            $rows[] = $parsed['row'];
        }

        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function normalizeHeader(array $header): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $key = $this->canonical((string) $col);
            if ($key !== '' && ! isset($map[$key])) {
                $map[$key] = $i;
            }
        }

        return $map;
    }

    private function canonical(string $header): string
    {
        $key = strtolower(trim($header));
        $key = str_replace([' ', '-'], '_', $key);

        return match ($key) {
            'inizio', 'started_at', 'start', 'data_inizio', 'dal', 'from' => 'inizio',
            'fine', 'ended_at', 'end', 'data_fine', 'al', 'to' => 'fine',
            'minuti_video', 'video_minutes', 'video', 'minuti_di_video', 'min_video' => 'minuti_video',
            'minuti_scorm', 'scorm_minutes', 'scorm', 'minuti_di_scorm', 'min_scorm' => 'minuti_scorm',
            'lezione', 'lesson', 'capitolo', 'lesson_title', 'titolo_lezione' => 'lezione',
            default => $key,
        };
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $map
     * @param  array<string, string>  $lessonIdsByNormalizedTitle
     * @return array{row: array{started_at: Carbon, ended_at: Carbon, video_seconds: int, scorm_seconds: int, lesson_id: ?string}}|array{error: string}
     */
    private function parseRow(array $row, array $map, array $lessonIdsByNormalizedTitle, int $line): array
    {
        $started = $this->parseWhen($this->cell($row, $map['inizio'] ?? null));
        $ended = $this->parseWhen($this->cell($row, $map['fine'] ?? null));
        if (! $started || ! $ended) {
            return ['error' => "Riga {$line}: inizio o fine non sono date valide. Formato atteso: gg/mm/aaaa hh:mm."];
        }

        if ($ended->lessThan($started)) {
            return ['error' => "Riga {$line}: la fine è precedente all'inizio."];
        }

        $video = $this->minutesToSeconds($this->cell($row, $map['minuti_video'] ?? null));
        $scorm = $this->minutesToSeconds($this->cell($row, $map['minuti_scorm'] ?? null));
        if ($video === null || $scorm === null) {
            return ['error' => "Riga {$line}: i minuti devono essere numeri maggiori o uguali a zero."];
        }

        $lessonTitle = $this->cell($row, $map['lezione'] ?? null);
        $lessonId = null;
        if ($lessonTitle !== '') {
            $lessonId = $lessonIdsByNormalizedTitle[mb_strtolower($lessonTitle)] ?? null;
            if ($lessonId === null) {
                return ['error' => "Riga {$line}: lezione «{$lessonTitle}» non trovata in questo corso."];
            }
        }

        return ['row' => [
            'started_at' => $started,
            'ended_at' => $ended,
            'video_seconds' => $video,
            'scorm_seconds' => $scorm,
            'lesson_id' => $lessonId,
        ]];
    }

    private function parseWhen(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach ([
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y H.i.s',
            'd/m/Y H.i',
            'd/m/Y',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
        ] as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            $clean = $errors === false
                || ((($errors['warning_count'] ?? 0) === 0) && (($errors['error_count'] ?? 0) === 0));

            if ($dt instanceof \DateTimeImmutable && $clean) {
                return Carbon::instance($dt);
            }
        }

        return null;
    }

    private function minutesToSeconds(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0;
        }

        $raw = str_replace(' ', '', $raw);
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '.', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $minutes = (float) $raw;
        if ($minutes < 0 || $minutes > 1_000_000) {
            return null;
        }

        return (int) round($minutes * 60);
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function cell(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return '';
        }

        return trim((string) $row[$index]);
    }
}
