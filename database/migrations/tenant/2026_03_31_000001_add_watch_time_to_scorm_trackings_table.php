<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scorm_trackings', function (Blueprint $table) {
            $table->unsignedInteger('watched_seconds')->default(0)->after('data_model');
            $table->timestamp('last_sync_at')->nullable()->after('watched_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('scorm_trackings', function (Blueprint $table) {
            $table->dropColumn(['watched_seconds', 'last_sync_at']);
        });
    }
};

