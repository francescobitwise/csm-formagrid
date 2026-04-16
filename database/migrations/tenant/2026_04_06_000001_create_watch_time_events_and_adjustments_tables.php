<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_time_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();

            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->index();

            $table->unsignedInteger('video_seconds')->default(0);
            $table->unsignedInteger('scorm_seconds')->default(0);
            $table->unsignedInteger('total_seconds')->default(0);

            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['enrollment_id', 'ended_at']);
            $table->index(['course_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_time_sessions');
    }
};

