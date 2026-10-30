<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ScormPlaybackProgress;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScormPlaybackProgressTest extends TestCase
{
    private ScormPlaybackProgress $progress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->progress = new ScormPlaybackProgress;
    }

    #[Test]
    public function an_open_page_does_not_add_time_without_a_playhead(): void
    {
        $now = CarbonImmutable::parse('2026-10-30 10:00:10');
        $last = $now->subSeconds(10);

        $result = $this->progress->apply(40, $last, [], $now, false, null);

        $this->assertSame(40, $result['watched_seconds']);
        $this->assertSame(0, $result['delta_seconds']);
        $this->assertFalse($result['data_model']['__playback_anchored'] ?? false);
    }

    #[Test]
    public function a_paused_playhead_replaces_time_counted_while_the_page_was_only_open(): void
    {
        $now = CarbonImmutable::parse('2026-10-30 10:00:10');

        $result = $this->progress->apply(54, $now->subSeconds(10), [], $now, false, 20);

        $this->assertSame(20, $result['watched_seconds']);
        $this->assertSame(0, $result['delta_seconds']);
        $this->assertTrue($result['data_model']['__playback_anchored']);
        $this->assertSame(20, $result['data_model']['__playback_seconds']);
    }

    #[Test]
    public function paused_playback_does_not_advance_after_the_playhead_is_known(): void
    {
        $now = CarbonImmutable::parse('2026-10-30 10:00:20');
        $model = ['__playback_anchored' => true, '__playback_seconds' => 20];

        $result = $this->progress->apply(20, $now->subSeconds(10), $model, $now, false, 20);

        $this->assertSame(20, $result['watched_seconds']);
        $this->assertSame(0, $result['delta_seconds']);
    }

    #[Test]
    public function playing_credits_only_the_playhead_advance_within_real_time(): void
    {
        $now = CarbonImmutable::parse('2026-10-30 10:00:30');
        $model = ['__playback_anchored' => true, '__playback_seconds' => 20];

        $result = $this->progress->apply(20, $now->subSeconds(10), $model, $now, true, 30);

        $this->assertSame(30, $result['watched_seconds']);
        $this->assertSame(10, $result['delta_seconds']);
    }

    #[Test]
    public function a_seek_forward_cannot_credit_more_than_the_elapsed_wall_time(): void
    {
        $now = CarbonImmutable::parse('2026-10-30 10:00:40');
        $model = ['__playback_anchored' => true, '__playback_seconds' => 20];

        $result = $this->progress->apply(20, $now->subSeconds(10), $model, $now, true, 62);

        $this->assertSame(30, $result['watched_seconds']);
        $this->assertSame(10, $result['delta_seconds']);
    }
}
