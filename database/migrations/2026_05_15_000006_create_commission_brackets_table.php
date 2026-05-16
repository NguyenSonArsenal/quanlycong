<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionBracketsTable extends Migration {
    public function up() {
        Schema::create('commission_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('position_code'); // NVBH_FT, NVBH_PT...
            $table->string('contract_type')->default('CT'); // CT, TV
            
            // Các mốc KPI (ví dụ 90, 100, 110, 120)
            $table->decimal('min_kpi', 8, 2);
            $table->decimal('max_kpi', 8, 2)->nullable();
            
            // Tỷ lệ hoa hồng (%)
            $table->decimal('commission_rate', 5, 2);
            
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('commission_brackets'); }
};
