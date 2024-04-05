<?php

use App\Enum\PaymentTypeEnum;
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
        Schema::create('trip_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trip_id')->nullable()->index();
            $table->foreignUuid('building_id')->nullable()->index();
            $table->foreignUuid('vehicle_id')->nullable()->index();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->uuid('public_id')->index();
            $table->enum('payment_type', [PaymentTypeEnum::CARD->value, PaymentTypeEnum::SUBSCRIPTION->value]);
            $table->double('amount');

            $table->softdeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_transactions');
    }
};
