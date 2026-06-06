<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planned_capacities', function (Blueprint $table) {
            $table->id();
            $table->string('department_code', 20);
            $table->date('work_date');
            $table->string('machine_code', 30); // Berisi 'GLOBAL' atau kode mesin spesifik
            $table->decimal('shift_1_hours', 5, 2);
            $table->decimal('shift_2_hours', 5, 2);
            $table->decimal('shift_3_hours', 5, 2);
            $table->text('notes')->nullable(); // Kolom catatan tipe TEXT
            $table->bigInteger('created_by')->unsigned()->nullable(); // ID pembuat awal
            $table->bigInteger('updated_by')->unsigned()->nullable(); // ID pengubah terakhir (Revisi #3 & Bonus)
            $table->timestamps();

            // Unique Constraint untuk mencegah tabrakan data (Revisi #1 & Keputusan Final)
            $table->unique(['department_code', 'work_date', 'machine_code'], 'uq_dept_date_machine');
            
            // Indexing untuk optimalisasi query
            $table->index(['department_code', 'work_date'], 'idx_dept_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_capacities');
    }
};
