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
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscribed_by')->nullable()->index();
            $table->foreignUuid('service_id')->nullable()->index();
            $table->foreignUuid('subscription_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->uuid('public_id')->index();

            $table->softdeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
