<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('md_heat_numbers_mirror', function (Blueprint $table) {
            if (!Schema::hasColumn('md_heat_numbers_mirror', 'traveler_number')) {
                $table->string('traveler_number', 60)->nullable()->unique()->after('id');
            }
        });

        // Drop composite unique constraint unique_heat_item on (heat_number, item_code)
        try {
            Schema::table('md_heat_numbers_mirror', function (Blueprint $table) {
                $table->dropUnique('unique_heat_item');
            });
        } catch (\Throwable $e) {
            // Constraint may not exist or has different name
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_heat_numbers_mirror', function (Blueprint $table) {
            if (Schema::hasColumn('md_heat_numbers_mirror', 'traveler_number')) {
                $table->dropColumn('traveler_number');
            }
            try {
                $table->unique(['heat_number', 'item_code'], 'unique_heat_item');
            } catch (\Throwable $e) {}
        });
    }
};
