<?php

use App\Enum\SubscriptionPaymentFrequencyEnum;
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
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->index();

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('amount');
            $table->integer('hours');
            $table->string('frequency')->default(SubscriptionPaymentFrequencyEnum::ANNUALLY->value);
            $table->string('status')->nullable();
            $table->boolean('active')->default(false);

            $table->softdeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
