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
        Schema::create('trip_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('tax_percentage');
            $table->integer('min_extension_time_buffer');
            $table->integer('subscriber_price_per_hour');
            $table->integer('cancellation_grace_hour');
            $table->integer('late_cancellation_charge_percent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_settings');
    }
};
