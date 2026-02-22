<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_images', function (Blueprint $table) {
            $table->id();

            // FK to rooms.id (must match rooms table id type)
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();

            $table->string('image_path');
            $table->unsignedTinyInteger('sort_order')->default(0); // 0,1,2
            $table->timestamps();

            $table->index(['room_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_images');
    }
};