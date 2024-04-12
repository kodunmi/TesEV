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
        Schema::create('package_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->foreignUuid('package_id')->nullable()->index();
            $table->dateTime('subscribed_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('frequency')->nullable();
            $table->integer('balance')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_user');
    }
};
