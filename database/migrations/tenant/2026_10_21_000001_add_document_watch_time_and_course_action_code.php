<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_time_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('watch_time_sessions', 'document_seconds')) {
                $table->unsignedInteger('document_seconds')->default(0)->after('scorm_seconds');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'action_code')) {
                $table->string('action_code', 64)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('watch_time_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('watch_time_sessions', 'document_seconds')) {
                $table->dropColumn('document_seconds');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'action_code')) {
                $table->dropColumn('action_code');
            }
        });
    }
};
