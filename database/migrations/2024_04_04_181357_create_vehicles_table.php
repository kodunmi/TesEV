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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Change id column to UUID and set it as the primary key
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            $table->string('status')->nullable(); //enabled disabled
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->string('plate_number')->nullable();
            $table->foreignUuid('building_id');
            $table->uuid('public_id');
            // Additional fields for the EV model
            $table->decimal('battery_capacity', 10, 2)->nullable();
            $table->integer('charging_time')->nullable();
            $table->decimal('range', 10, 2)->nullable();
            $table->string('power_output')->nullable();
            $table->string('acceleration')->nullable();
            $table->string('charging_connector_type')->nullable();
            $table->string('energy_efficiency')->nullable();
            $table->string('charging_network')->nullable();
            $table->string('battery_warranty')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
