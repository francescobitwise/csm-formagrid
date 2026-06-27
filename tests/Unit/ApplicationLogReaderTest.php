<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ApplicationLogReader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationLogReaderTest extends TestCase
{
    #[Test]
    public function rejects_path_traversal_in_filename(): void
    {
        $reader = new ApplicationLogReader;

        $result = $reader->read('../.env');

        $this->assertSame([], $result['lines']);
    }

    #[Test]
    public function reads_tail_from_temporary_log_file(): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $basename = 'laravel-2099-01-01.log';
        $path = $dir.DIRECTORY_SEPARATOR.$basename;

        file_put_contents($path, "[2026-06-25 10:00:00] production.INFO: first\n[2026-06-25 10:00:01] production.ERROR: boom\n");

        try {
            $reader = new ApplicationLogReader;
            $result = $reader->read($basename, 10, 'ERROR');

            $this->assertCount(1, $result['lines']);
            $this->assertStringContainsString('ERROR: boom', $result['lines'][0]);
        } finally {
            @unlink($path);
        }
    }
}
