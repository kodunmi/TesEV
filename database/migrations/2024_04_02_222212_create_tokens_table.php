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
        Schema::create('tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('token')->nullable();
            $table->string('purpose')->nullable();
            $table->string('recipient')->nullable();
            $table->string('channel')->nullable();
            $table->integer('ttl')->default(5);
            $table->string('data')->nullable();
            $table->timestampTz('expired_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->boolean('valid')->default(true);
            $table->json('meta')->nullable();
            $table->uuid('public_id')->index();

            $table->timestamps();
            $table->softdeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
