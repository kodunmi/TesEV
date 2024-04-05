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
        Schema::create('trip_meta_data', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('public_id');
            $table->foreignUuid('trip_id');

            $table->boolean('distance_covered')->default(false);
            $table->boolean('remove_belongings')->default(false);
            $table->boolean('remove_trash')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_meta_data');
    }
};
