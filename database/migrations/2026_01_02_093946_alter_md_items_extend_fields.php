<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('md_items', function (Blueprint $table) {

            if (!Schema::hasColumn('md_items', 'aisi')) {
                $table->string('aisi', 50)->nullable()->after('name');
            }

            if (!Schema::hasColumn('md_items', 'standard')) {
                $table->string('standard', 50)->nullable()->after('aisi');
            }

            if (!Schema::hasColumn('md_items', 'unit_weight')) {
                $table->decimal('unit_weight', 10, 3)->nullable()->after('standard');
            }

            if (!Schema::hasColumn('md_items', 'department_code')) {
                $table->string('department_code', 30)->after('unit_weight');
            }

            if (!Schema::hasColumn('md_items', 'cycle_time_sec')) {
                $table->integer('cycle_time_sec')->after('department_code');
            }

            if (!Schema::hasColumn('md_items', 'status')) {
                $table->string('status', 20)->default('active')->after('cycle_time_sec');
            }
        });
    }

    public function down(): void
    {
        // ❌ Master data: no rollback
    }
};
