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
        Schema::table('md_items_mirror', function (Blueprint $table) {

            if (!Schema::hasColumn('md_items_mirror', 'department_code')) {
                $table->string('department_code', 30)
                      ->after('name');
            }

            if (!Schema::hasColumn('md_items_mirror', 'aisi')) {
                $table->string('aisi', 50)
                      ->nullable()
                      ->after('department_code');
            }

            if (!Schema::hasColumn('md_items_mirror', 'standard')) {
                $table->string('standard', 50)
                      ->nullable()
                      ->after('aisi');
            }

            if (!Schema::hasColumn('md_items_mirror', 'unit_weight')) {
                $table->decimal('unit_weight', 10, 3)
                      ->nullable()
                      ->after('standard');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_items_mirror', function (Blueprint $table) {

            if (Schema::hasColumn('md_items_mirror', 'unit_weight')) {
                $table->dropColumn('unit_weight');
            }

            if (Schema::hasColumn('md_items_mirror', 'standard')) {
                $table->dropColumn('standard');
            }

            if (Schema::hasColumn('md_items_mirror', 'aisi')) {
                $table->dropColumn('aisi');
            }

            if (Schema::hasColumn('md_items_mirror', 'department_code')) {
                $table->dropColumn('department_code');
            }
        });
    }
};
