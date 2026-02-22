<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Drop foreign key first (if exists), then make column nullable, then re-add FK
        Schema::table('rooms', function (Blueprint $table) {
            // default Laravel FK name: rooms_property_id_foreign
            try {
                $table->dropForeign(['property_id']);
            } catch (\Throwable $e) {
                // ignore if FK does not exist
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            // ✅ make nullable
            $table->unsignedBigInteger('property_id')->nullable()->change();
        });

        Schema::table('rooms', function (Blueprint $table) {
            // ✅ re-add FK, and if property deleted set null (better for your use case)
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            try {
                $table->dropForeign(['property_id']);
            } catch (\Throwable $e) {
                // ignore
            }
        });

        // ⚠️ Before making NOT NULL again, ensure no rows have NULL property_id
        DB::table('rooms')->whereNull('property_id')->update(['property_id' => 1]);

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->cascadeOnDelete();
        });
    }
};