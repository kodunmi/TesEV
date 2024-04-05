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
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id');
            $table->string('booking_id');

            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();

            $table->boolean('started_trip')->default(false);
            $table->boolean('ended_trip')->default(false);

            $table->foreignUuid('user_id');
            $table->foreignUuid('vehicle_id');

            $table->foreignUuid('parent_trip_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
