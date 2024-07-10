<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('building_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('building_id');
            $table->foreignUuid('user_id');
            $table->dateTime('date_added')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('date_approved')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_user');
    }
};
