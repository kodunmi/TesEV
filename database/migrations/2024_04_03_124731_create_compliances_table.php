<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compliances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->nullable();
            $table->foreignUuid('user_id');
            $table->foreignUuid('driver_license_front')->nullable();
            $table->foreignUuid('driver_license_back')->nullable();
            $table->foreignUuid('photo')->nullable();
            $table->string('license_state')->nullable();
            $table->string('poster_code')->nullable();
            $table->string('license_number')->nullable();
            $table->string('expiration_date')->nullable();
            $table->boolean('license_verified')->default(false);
            $table->boolean('active')->default(false);
            $table->dateTime('license_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliances');
    }
};
