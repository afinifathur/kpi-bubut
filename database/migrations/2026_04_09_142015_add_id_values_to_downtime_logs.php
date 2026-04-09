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
            $table->integer('rpm_id_value')->nullable()->after('rpm_value');
            $table->decimal('feeding_id_value', 8, 3)->nullable()->after('feeding_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downtime_logs', function (Blueprint $table) {
            $table->dropColumn(['rpm_id_value', 'feeding_id_value']);
        });
    }
};
