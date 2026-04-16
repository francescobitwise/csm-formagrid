<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('description');
            $table->decimal('total_hours', 8, 2)->nullable()->after('starts_at');
            $table->boolean('is_visible')->default(true)->after('total_hours');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'total_hours', 'is_visible']);
        });
    }
};
