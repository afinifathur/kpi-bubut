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
            // Drop foreign keys to allow cross-database references to master users table
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['submitted_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_reports', function (Blueprint $table) {
            // Re-add foreign keys if needed
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
