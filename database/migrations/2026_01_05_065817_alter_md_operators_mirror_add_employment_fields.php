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
        Schema::table('md_operators_mirror', function (Blueprint $table) {

            if (!Schema::hasColumn('md_operators_mirror', 'employment_seq')) {
                $table->unsignedTinyInteger('employment_seq')
                      ->default(1)
                      ->after('code');
            }

            if (!Schema::hasColumn('md_operators_mirror', 'join_date')) {
                $table->date('join_date')
                      ->nullable()
                      ->after('employment_seq');
            }

            if (!Schema::hasColumn('md_operators_mirror', 'employment_type')) {
                $table->string('employment_type', 20)
                      ->nullable()
                      ->after('join_date');
            }

            if (!Schema::hasColumn('md_operators_mirror', 'position')) {
                $table->string('position', 50)
                      ->nullable()
                      ->after('employment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_operators_mirror', function (Blueprint $table) {

            if (Schema::hasColumn('md_operators_mirror', 'position')) {
                $table->dropColumn('position');
            }

            if (Schema::hasColumn('md_operators_mirror', 'employment_type')) {
                $table->dropColumn('employment_type');
            }

            if (Schema::hasColumn('md_operators_mirror', 'join_date')) {
                $table->dropColumn('join_date');
            }

            if (Schema::hasColumn('md_operators_mirror', 'employment_seq')) {
                $table->dropColumn('employment_seq');
            }
        });
    }
};
