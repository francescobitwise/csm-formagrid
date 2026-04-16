<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime', 127)->nullable();
            $table->timestamps();

            $table->unique('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_lessons');
    }
};
