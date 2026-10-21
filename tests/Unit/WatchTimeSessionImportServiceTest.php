<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\WatchTimeSessionImportService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WatchTimeSessionImportServiceTest extends TestCase
{
    private WatchTimeSessionImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WatchTimeSessionImportService;
    }

    #[Test]
    public function it_parses_italian_semicolon_csv_with_lesson_match(): void
    {
        $path = $this->csv("\xEF\xBB\xBF"."inizio;fine;minuti_video;minuti_scorm;lezione\n"
            ."28/03/2026 09:00;28/03/2026 10:30;90;0;\n"
            ."28/03/2026 14:00;28/03/2026 15:00;0;1,5;Introduzione\n"
            ."32/13/2026 09:00;28/03/2026 10:00;10;0;\n"
            ."28/03/2026 18:00;28/03/2026 17:00;10;0;\n"
            ."28/03/2026 11:00;28/03/2026 12:00;5;0;Capitolo assente\n");

        $parsed = $this->service->parse($path, ['introduzione' => 'lesson-1']);

        $this->assertCount(2, $parsed['rows']);
        $this->assertSame(90 * 60, $parsed['rows'][0]['video_seconds']);
        $this->assertNull($parsed['rows'][0]['lesson_id']);
        $this->assertSame(90, $parsed['rows'][1]['scorm_seconds']);
        $this->assertSame('lesson-1', $parsed['rows'][1]['lesson_id']);
        $this->assertSame('2026-03-28 09:00', $parsed['rows'][0]['started_at']->format('Y-m-d H:i'));
        $this->assertCount(3, $parsed['errors']);
        $this->assertStringContainsString('date valide', $parsed['errors'][0]);
        $this->assertStringContainsString('precedente', $parsed['errors'][1]);
        $this->assertStringContainsString('Capitolo assente', $parsed['errors'][2]);
    }

    #[Test]
    public function it_accepts_english_comma_headers(): void
    {
        $path = $this->csv("started_at,ended_at,video_minutes,scorm_minutes,lesson\n"
            ."2026-04-01 08:15,2026-04-01 09:00,45,0,Modulo 1\n");

        $parsed = $this->service->parse($path, ['modulo 1' => 'abc']);

        $this->assertCount(1, $parsed['rows']);
        $this->assertSame([], $parsed['errors']);
        $this->assertSame(45 * 60, $parsed['rows'][0]['video_seconds']);
        $this->assertSame('abc', $parsed['rows'][0]['lesson_id']);
    }

    #[Test]
    public function it_rejects_a_header_without_required_columns(): void
    {
        $path = $this->csv("nome;email\nMario;mario@example.test\n");

        $parsed = $this->service->parse($path, []);

        $this->assertSame([], $parsed['rows']);
        $this->assertStringContainsString('inizio', $parsed['errors'][0]);
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wts');
        file_put_contents($path, $contents);

        return $path;
    }
}
