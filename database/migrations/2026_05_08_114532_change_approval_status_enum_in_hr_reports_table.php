<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update approval_status enum to include new statuses while keeping pending for data safety
        DB::statement("ALTER TABLE hr_reports MODIFY COLUMN approval_status ENUM('draft', 'pending', 'submitted', 'approved', 'rejected') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE hr_reports MODIFY COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
