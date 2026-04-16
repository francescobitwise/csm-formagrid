<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('course_module', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('module_id')->constrained('modules')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'module_id']);
            $table->index(['course_id', 'position']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 32)->index();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_module');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('courses');
    }
};

