<?php

use App\Enum\TripStatusEnum;
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

            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();

            $table->dateTime('penalty_started_at')->nullable();
            $table->dateTime('penalty_ended_at')->nullable();

            $table->boolean('started_trip')->default(false);
            $table->boolean('ended_trip')->default(false);

            $table->foreignUuid('user_id')->nullable();
            $table->foreignUuid('vehicle_id')->nullable();

            $table->foreignUuid('parent_trip_id')->nullable();

            $table->double('tax_amount')->default(0.00);
            $table->double('tax_percentage')->default(0.00);

            $table->enum('status', TripStatusEnum::values())->default(TripStatusEnum::PENDING->value); // started, ended, pending, canceled, reserved,

            $table->boolean('remove_belongings')->default(false);
            $table->boolean('remove_trash')->default(false);
            $table->boolean('plug_vehicle')->default(false);
            $table->boolean('added_extra_time')->default(false);

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
