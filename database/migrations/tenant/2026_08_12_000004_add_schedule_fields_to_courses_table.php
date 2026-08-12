<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'schedule_enabled' => fn (Blueprint $table) => $table->boolean('schedule_enabled')->default(false)->after('auto_enroll'),
            'schedule_weekdays' => fn (Blueprint $table) => $table->json('schedule_weekdays')->nullable()->after('schedule_enabled'),
            'schedule_opens_at' => fn (Blueprint $table) => $table->time('schedule_opens_at')->nullable()->after('schedule_weekdays'),
            'schedule_closes_at' => fn (Blueprint $table) => $table->time('schedule_closes_at')->nullable()->after('schedule_opens_at'),
            'night_schedule_enabled' => fn (Blueprint $table) => $table->boolean('night_schedule_enabled')->default(false)->after('schedule_closes_at'),
            'night_opens_at' => fn (Blueprint $table) => $table->time('night_opens_at')->nullable()->after('night_schedule_enabled'),
            'night_closes_at' => fn (Blueprint $table) => $table->time('night_closes_at')->nullable()->after('night_opens_at'),
        ];

        $missing = array_keys(array_filter(
            $columns,
            fn (callable $add, string $name) => ! Schema::hasColumn('courses', $name),
            ARRAY_FILTER_USE_BOTH
        ));

        if ($missing === []) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) use ($columns, $missing) {
            foreach ($missing as $name) {
                $columns[$name]($table);
            }
        });
    }

    public function down(): void
    {
        $cols = [
            'schedule_enabled',
            'schedule_weekdays',
            'schedule_opens_at',
            'schedule_closes_at',
            'night_schedule_enabled',
            'night_opens_at',
            'night_closes_at',
        ];

        $existing = array_values(array_filter(
            $cols,
            fn (string $col) => Schema::hasColumn('courses', $col)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
