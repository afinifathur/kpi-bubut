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
        // Final cleanup: Remove legacy 'pending' status from the enum
        // This is safe because previous migrations have already normalized data to 'draft'
        DB::statement("ALTER TABLE hr_reports MODIFY COLUMN approval_status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore 'pending' to enum if rolled back
        DB::statement("ALTER TABLE hr_reports MODIFY COLUMN approval_status ENUM('draft', 'pending', 'submitted', 'approved', 'rejected') DEFAULT 'draft'");
    }
};
