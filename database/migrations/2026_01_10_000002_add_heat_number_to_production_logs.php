<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_logs', function (Blueprint $blueprint) {
            $blueprint->string('heat_number', 50)->nullable()->after('item_code');
            $blueprint->index('heat_number');
        });
    }

    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $blueprint) {
            $blueprint->dropColumn('heat_number');
        });
    }
};
