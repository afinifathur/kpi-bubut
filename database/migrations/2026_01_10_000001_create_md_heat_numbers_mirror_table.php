<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('md_heat_numbers_mirror', function (Blueprint $blueprint) {
            $blueprint->string('heat_number', 50)->primary();
            $blueprint->string('kode_produksi', 50)->nullable();
            $blueprint->string('item_code', 50);
            $blueprint->string('item_name', 200)->nullable();
            $blueprint->integer('cor_qty')->default(0);
            $blueprint->string('status', 20)->default('active');

            $blueprint->timestamp('source_updated_at')->nullable();
            $blueprint->timestamp('last_sync_at')->nullable();

            $blueprint->index('item_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('md_heat_numbers_mirror');
    }
};
