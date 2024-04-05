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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->index();
            $table->bigInteger('amount')->default(0);
            $table->string('reference')->nullable()->index();
            $table->string('narration')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();               // successful or failed or processing or initiated or abandoned
            $table->enum('entry', ['credit', 'debit'])->default('credit'); // credit or debit
            $table->string('type')->nullable();
            $table->string('channel')->nullable()->default('web');
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
