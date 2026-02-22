<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            // Room belongs to a property
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

            // Room details
            $table->string('room_name');
            $table->string('room_type')->nullable(); // Deluxe, Suite, Twin, etc.
            $table->string('bed_type')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('total_rooms')->default(1);

            // Pricing
            $table->decimal('price', 12, 2)->default(0);

            // Optional fields
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('room_name');
            $table->index('room_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
