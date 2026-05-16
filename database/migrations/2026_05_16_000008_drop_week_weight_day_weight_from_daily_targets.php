<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_targets', function (Blueprint $table) {
            $table->dropColumn(['week_weight', 'day_weight']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_targets', function (Blueprint $table) {
            $table->float('week_weight')->default(0)->after('week_number');
            $table->float('day_weight')->default(0)->after('week_weight');
        });
    }
};
