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
        Schema::create('buildings', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Use UUID as primary key
            $table->string('name')->nullable(); // The name of the building
            $table->uuid('public_id'); // The public identifier of the building
            $table->integer('code')->unique(); // The public identifier of the building
            $table->string('address')->nullable(); // The address of the building
            $table->time('opening_time')->nullable(); // The opening time of the building
            $table->time('closing_time')->nullable(); // The closing time of the building
            $table->string('status')->default('active'); // The status of the building (e.g., active, inactive)
            $table->foreignUuid('image')->nullable(); // The image URL or path of the building
            $table->text('description')->nullable(); // A brief description or summary of the building
            $table->string('type')->nullable(); // The type or category of the building (e.g., residential, commercial, industrial)
            $table->integer('floor_count')->nullable(); // The number of floors or levels in the building
            $table->integer('built_year')->nullable(); // The year when the building was constructed
            $table->string('contact_person')->nullable(); // The name of the contact person associated with the building
            $table->string('contact_email')->nullable(); // The email address of the contact person
            $table->string('contact_phone')->nullable(); // The phone number of the contact person
            $table->decimal('latitude', 10, 8)->nullable(); // The latitude coordinate of the building location
            $table->decimal('longitude', 11, 8)->nullable(); // The longitude coordinate of the building location
            $table->string('construction_material')->nullable(); // The primary material used in the construction of the building
            $table->string('architect')->nullable(); // The name of the architect or architectural firm responsible for designing the building
            $table->string('construction_company')->nullable(); // The name of the construction company that built the building
            $table->string('maintenance_company')->nullable(); // The name of the company responsible for building maintenance
            $table->string('security_level')->nullable(); // The level of security measures implemented in the building
            $table->string('insurance_policy_number')->nullable(); // The policy number of the insurance covering the building
            $table->date('last_inspection_date')->nullable(); // The date of the last inspection conducted on the building

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
