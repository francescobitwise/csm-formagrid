<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\HlsPlaylist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HlsPlaylistTest extends TestCase
{
    #[Test]
    public function rewrite_points_segments_at_the_given_url(): void
    {
        $playlist = new HlsPlaylist;
        $body = "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=800000\n720/index.m3u8\n";

        $out = $playlist->rewrite($body, 'tenants/demo/master.m3u8', function (string $dir, string $ref): ?string {
            $this->assertSame('tenants/demo', $dir);

            return 'https://app.test/preview/hls/'.$ref;
        });

        $this->assertStringContainsString('https://app.test/preview/hls/720/index.m3u8', $out);
        $this->assertStringNotContainsString('tenants/demo/720', $out);
    }

    #[Test]
    public function relative_key_rejects_parent_segments(): void
    {
        $playlist = new HlsPlaylist;

        $this->assertNull($playlist->resolveRelativeObjectKey('tenants/demo', '../secret.ts'));
        $this->assertNull($playlist->resolveRelativeObjectKey('tenants/demo', 'https://evil.test/a.ts'));
    }
}
