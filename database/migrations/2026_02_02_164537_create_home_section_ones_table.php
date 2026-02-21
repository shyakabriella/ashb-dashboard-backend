<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('home_section_ones', function (Blueprint $table) {
      $table->id();

      $table->string('image')->nullable();   // stored path e.g. home/section1/xxx.jpg
      $table->string('title');
      $table->string('subtitle')->nullable();

      $table->boolean('is_active')->default(true);

      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('home_section_ones');
  }
};