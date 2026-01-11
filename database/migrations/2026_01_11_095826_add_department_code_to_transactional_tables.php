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
        Schema::table('production_logs', function (Blueprint $table) {
            $table->string('department_code', 20)->nullable()->after('id');
            $table->index('department_code');
        });

        Schema::table('reject_logs', function (Blueprint $table) {
            $table->string('department_code', 20)->nullable()->after('id');
            $table->index('department_code');
        });

        Schema::table('downtime_logs', function (Blueprint $table) {
            $table->string('department_code', 20)->nullable()->after('id');
            $table->index('department_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn('department_code');
        });

        Schema::table('reject_logs', function (Blueprint $table) {
            $table->dropColumn('department_code');
        });

        Schema::table('downtime_logs', function (Blueprint $table) {
            $table->dropColumn('department_code');
        });
    }
};
