<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255)->index();
            $table->string('legal_name', 255)->nullable();
            $table->string('vat', 32)->nullable()->index();

            $table->string('email', 255)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('contact_name', 255)->nullable();

            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('province', 64)->nullable();
            $table->string('country', 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

