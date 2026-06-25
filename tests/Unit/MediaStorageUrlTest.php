<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaStorageUrlTest extends TestCase
{
    #[Test]
    public function local_disk_uses_public_url_without_signature(): void
    {
        config([
            'media.disk' => 'public',
            'filesystems.disks.public.url' => 'https://app.test/storage',
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('tenants/demo/courses/x/cover.jpg', 'img');

        $url = MediaStorage::url('tenants/demo/courses/x/cover.jpg');

        $this->assertStringContainsString('/storage/', $url);
        $this->assertStringNotContainsString('X-Amz-Signature', $url);
    }

    #[Test]
    public function s3_without_acl_uses_presigned_url_by_default(): void
    {
        config([
            'media.disk' => 's3',
            'media.signed_object_urls' => null,
            'media.upload_visibility' => 'public',
            'media.s3_put_acl' => false,
            'media.signed_object_ttl_minutes' => 60,
            'filesystems.disks.s3.driver' => 's3',
            'filesystems.disks.s3.key' => 'test',
            'filesystems.disks.s3.secret' => 'test',
            'filesystems.disks.s3.region' => 'eu-west-1',
            'filesystems.disks.s3.bucket' => 'test-bucket',
        ]);

        $url = MediaStorage::url('tenants/demo/courses/x/cover.jpg');

        $this->assertStringContainsString('X-Amz-Signature', $url);
        $this->assertStringContainsString('tenants/demo/courses/x/cover.jpg', $url);
    }

    #[Test]
    public function s3_public_cdn_can_opt_out_of_signed_urls(): void
    {
        config([
            'media.disk' => 's3',
            'media.signed_object_urls' => false,
            'media.upload_visibility' => 'public',
            'media.s3_put_acl' => false,
            'filesystems.disks.s3.driver' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.example.com',
        ]);

        Storage::shouldReceive('disk')->with('s3')->andReturnSelf();
        Storage::shouldReceive('url')->with('tenants/demo/cover.jpg')->andReturn('https://cdn.example.com/tenants/demo/cover.jpg');

        $url = MediaStorage::url('tenants/demo/cover.jpg');

        $this->assertSame('https://cdn.example.com/tenants/demo/cover.jpg', $url);
    }
}
