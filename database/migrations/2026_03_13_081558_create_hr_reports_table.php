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
        Schema::create('hr_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->date('report_date');
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->string('data_link')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->enum('status', ['Open', 'Investigating', 'Action Plan', 'Monitoring', 'Closed'])->default('Open');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_reports');
    }
};
