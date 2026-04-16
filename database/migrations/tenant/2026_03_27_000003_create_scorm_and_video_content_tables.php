<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorm_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('s3_path');
            $table->json('manifest')->nullable();
            $table->string('version', 16)->index();
            $table->string('status', 32)->default('processing')->index();
            $table->timestamps();
        });

        Schema::create('video_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('original_s3');
            $table->string('hls_manifest')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 32)->default('processing')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_lessons');
        Schema::dropIfExists('scorm_packages');
    }
};

