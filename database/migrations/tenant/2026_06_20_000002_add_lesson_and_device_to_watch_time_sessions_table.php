<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_time_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('watch_time_sessions', 'lesson_id')) {
                $table->foreignUuid('lesson_id')->nullable()->after('course_id')->constrained('lessons')->nullOnDelete();
            }
            if (! Schema::hasColumn('watch_time_sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('total_seconds');
            }
            if (! Schema::hasColumn('watch_time_sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            $table->index(['lesson_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('watch_time_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('watch_time_sessions', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('watch_time_sessions', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('watch_time_sessions', 'lesson_id')) {
                $table->dropForeign(['lesson_id']);
                $table->dropColumn('lesson_id');
            }
        });
    }
};

