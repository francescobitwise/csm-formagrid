<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_contact_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('recorded_by_user_id')->nullable()->index();
            $table->string('contact_email', 255);
            $table->string('request_type', 64);
            $table->text('message');
            $table->string('status', 32)->default('new')->index();
            $table->timestamps();

            $table->foreign('recorded_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_contact_requests');
    }
};
