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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->index();
            $table->double('amount')->default(0.00);
            $table->double('total_amount')->default(0.00);
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('narration')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('pending');               // successful or failed or processing or initiated or abandoned
            $table->enum('entry', ['credit', 'debit'])->default('credit'); // credit or debit
            $table->string('type')->nullable();
            $table->enum('channel', PaymentTypeEnum::values())->default(PaymentTypeEnum::SUBSCRIPTION->value);
            $table->double('tax_amount')->default(0.00);
            $table->double('tax_percentage')->default(0.00);
            $table->json('meta')->nullable();
            $table->json('object')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->uuidMorphs('transactable');
            $table->softdeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
