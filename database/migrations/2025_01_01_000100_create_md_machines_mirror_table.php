<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('md_machines_mirror', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('department_code', 20);
            $table->string('line_code', 20)->nullable();

            // DB status (structural)
            $table->string('status', 20);

            // runtime status from Master (computed)
            $table->string('runtime_status', 20);

            // observability
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_active_module', 50)->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            $table->index(['department_code', 'status']);
            $table->index('runtime_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('md_machines_mirror');
    }
};
