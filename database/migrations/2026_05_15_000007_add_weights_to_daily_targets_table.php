<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Drop migration 000007 vì daily_targets đã được tích hợp vào 000004
class AddWeightsToDailyTargetsTable extends Migration {
    public function up() { /* Already handled in 000004 */ }
    public function down() { /* Already handled in 000004 */ }
};
