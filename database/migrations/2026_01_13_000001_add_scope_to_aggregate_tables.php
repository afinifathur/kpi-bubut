<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add department_code to aggregate tables
        Schema::table('daily_kpi_operator', function (Blueprint $table) {
            $table->string('department_code', 20)->nullable()->after('id');
            $table->index('department_code');
        });

        Schema::table('daily_kpi_machine', function (Blueprint $table) {
            $table->string('department_code', 20)->nullable()->after('id');
            $table->index('department_code');
        });

        // 2. Migrate existing data to '404.1' (Bubut Flange)
        $defaultDept = '404.1';

        DB::table('production_logs')->whereNull('department_code')->update(['department_code' => $defaultDept]);
        DB::table('reject_logs')->whereNull('department_code')->update(['department_code' => $defaultDept]);
        DB::table('downtime_logs')->whereNull('department_code')->update(['department_code' => $defaultDept]);

        DB::table('daily_kpi_operator')->whereNull('department_code')->update(['department_code' => $defaultDept]);
        DB::table('daily_kpi_machine')->whereNull('department_code')->update(['department_code' => $defaultDept]);

        // Also update Mirror tables if they have null department_code
        DB::table('md_operators_mirror')->whereNull('department_code')->update(['department_code' => $defaultDept]);
        DB::table('md_machines_mirror')->whereNull('department_code')->update(['department_code' => $defaultDept]);
        DB::table('md_items_mirror')->whereNull('department_code')->update(['department_code' => $defaultDept]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_kpi_operator', function (Blueprint $table) {
            $table->dropColumn('department_code');
        });

        Schema::table('daily_kpi_machine', function (Blueprint $table) {
            $table->dropColumn('department_code');
        });
    }
};
