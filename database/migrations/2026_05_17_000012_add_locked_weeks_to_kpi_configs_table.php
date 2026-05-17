<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLockedWeeksToKpiConfigsTable extends Migration
{
    public function up()
    {
        Schema::table('kpi_configs', function (Blueprint $table) {
            $table->json('locked_weeks')->nullable()->after('shift_ratios_weekend');
        });
    }

    public function down()
    {
        Schema::table('kpi_configs', function (Blueprint $table) {
            $table->dropColumn('locked_weeks');
        });
    }
}
