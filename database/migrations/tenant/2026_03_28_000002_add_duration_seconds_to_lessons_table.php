<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('required');
        });

        foreach (DB::table('video_lessons')->whereNotNull('duration_seconds')->get() as $row) {
            DB::table('lessons')
                ->where('id', $row->lesson_id)
                ->whereNull('duration_seconds')
                ->update(['duration_seconds' => $row->duration_seconds]);
        }
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }
};
