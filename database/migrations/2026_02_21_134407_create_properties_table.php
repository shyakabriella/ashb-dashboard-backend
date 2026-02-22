<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            // Basic
            $table->string('property_name');
            $table->string('property_type')->default('Hotel');
            $table->string('star_rating')->nullable();

            // Logo file path
            $table->string('logo')->nullable();

            // Contact
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');

            // Location
            $table->string('country')->default('Rwanda');
            $table->string('city');
            $table->string('address')->nullable();

            // Progress
            $table->string('onboarding_stage')->default('Draft');
            $table->string('ota_status')->default('Not Started');
            $table->string('seo_status')->default('Not Started');

            // Services (JSON array)
            $table->json('services')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index('property_name');
            $table->index('city');
            $table->index('onboarding_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};