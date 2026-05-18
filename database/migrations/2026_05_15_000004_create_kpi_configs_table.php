<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiConfigsTable extends Migration {
    public function up() {
        Schema::create('kpi_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('month'); // YYYY-MM
            $table->decimal('total_target', 15, 2);

            // Tỷ trọng tuần - lưu dạng JSON: {1: 20, 2: 20, 3: 20, 4: 20, 5: 20}
            $table->json('weekly_ratios')->nullable();

            // Tỷ trọng ngày trong tuần - lưu JSON: {1:x,2:x,3:x,4:x,5:x,6:x,7:x}
            // Key: 1=T2, 2=T3, ..., 7=CN
            $table->json('daily_ratios')->nullable();

            // Tỷ trọng ca - riêng weekday và weekend
            // weekday: {morning: 10, afternoon: 36, evening: 54}
            $table->json('shift_ratios_weekday')->nullable();
            // weekend: {morning: 12, afternoon: 45, evening: 43}
            $table->json('shift_ratios_weekend')->nullable();

            $table->boolean('is_saved')->default(false);

            $table->unique(['store_id', 'month']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('daily_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_config_id');
            $table->date('date');
            $table->integer('week_number')->default(1);    // Tuần 1-5 trong tháng
            $table->decimal('week_weight', 6, 2)->default(0); // % tỷ trọng tuần
            $table->decimal('day_weight', 6, 2)->default(0);  // % tỷ trọng ngày trong tuần
            $table->decimal('target_amount', 15, 2)->default(0);         // Target gốc
            $table->decimal('rebalanced_target', 15, 2)->nullable();     // Target sau rebalance
            $table->unique(['kpi_config_id', 'date']);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('daily_targets');
        Schema::dropIfExists('kpi_configs');
    }
};
