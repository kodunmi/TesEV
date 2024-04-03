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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->text('preview')->nullable();
            $table->text('channel')->nullable();
            $table->text('url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('show')->default(true);
            $table->json('read_by')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('sent_by')->nullable();
            $table->string('type')->nullable();
            $table->longText('markup_body')->nullable();
            $table->json('meta')->nullable();
            $table->json('data')->nullable();
            $table->json('attachments')->nullable();
            $table->uuid('public_id')->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
