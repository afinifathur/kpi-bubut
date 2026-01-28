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
        Schema::create('daily_locks', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // One record per date
            $table->boolean('is_locked')->default(true);
            $table->unsignedBigInteger('unlocked_by')->nullable(); // Who manually toggled it last
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_locks');
    }
};
