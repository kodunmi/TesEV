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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner_id')->nullable()->index();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('number')->nullable();
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->string('extension')->nullable();
            $table->string('size')->nullable();
            $table->string('file_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('folder')->nullable();
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
        Schema::dropIfExists('files');
    }
};
