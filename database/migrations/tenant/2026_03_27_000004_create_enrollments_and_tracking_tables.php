<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->decimal('progress_pct', 5, 2)->default(0);
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('scorm_trackings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('scorm_package_id')->constrained('scorm_packages')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('status', 32)->default('not_attempted')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->longText('suspend_data')->nullable();
            $table->json('data_model')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scorm_package_id', 'enrollment_id']);
        });

        Schema::create('video_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('video_lesson_id')->constrained('video_lessons')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('last_position')->default(0);
            $table->boolean('completed')->default(false)->index();
            $table->timestamps();

            $table->unique(['user_id', 'video_lesson_id', 'enrollment_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('scorm_trackings');
        Schema::dropIfExists('enrollments');
    }
};
