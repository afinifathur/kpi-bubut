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
        Schema::table('hr_reports', function (Blueprint $table) {
            $table->date('target_completion_date')->nullable()->after('corrective_action');
            $table->text('monitoring_result')->nullable()->after('target_completion_date');
            $table->json('evidence_files')->nullable()->after('monitoring_result');
            $table->text('additional_notes')->nullable()->after('evidence_files');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_reports', function (Blueprint $table) {
            $table->dropColumn(['target_completion_date', 'monitoring_result', 'evidence_files', 'additional_notes']);
        });
    }
};
