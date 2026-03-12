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
        Schema::table('downtime_logs', function (Blueprint $table) {
            // New entry type
            $table->string('entry_type', 20)->default('downtime')->after('id');

            // Support for shift and operator if they are needed, but made nullable as per request
            $table->string('shift', 10)->nullable()->after('downtime_date');
            $table->string('operator_code', 50)->nullable()->change();
            $table->string('tim', 50)->nullable()->change();

            // Downtime specific
            $table->datetime('start_time')->nullable()->after('shift');
            $table->datetime('end_time')->nullable()->after('start_time');
            $table->string('reason')->nullable()->after('end_time');

            // Daily Check specific
            $table->string('size_category', 50)->nullable()->after('reason');
            $table->string('check_cekam', 10)->nullable();
            $table->string('check_air_ozo', 10)->nullable();
            $table->string('check_eretan', 10)->nullable();
            $table->string('check_pisau', 10)->nullable();
            $table->string('check_kebersihan', 10)->nullable();
            $table->string('check_oli', 10)->nullable();
            $table->integer('rpm_value')->nullable();
            $table->float('feeding_value')->nullable();
            $table->string('rpm_feeding_mode', 20)->nullable(); // kasar / finish
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downtime_logs', function (Blueprint $table) {
            $table->dropColumn([
                'entry_type', 'shift', 'start_time', 'end_time', 'reason',
                'size_category', 'check_cekam', 'check_air_ozo', 'check_eretan',
                'check_pisau', 'check_kebersihan', 'check_oli', 'rpm_value',
                'feeding_value', 'rpm_feeding_mode'
            ]);
            $table->string('operator_code', 50)->nullable(false)->change();
        });
    }
};
