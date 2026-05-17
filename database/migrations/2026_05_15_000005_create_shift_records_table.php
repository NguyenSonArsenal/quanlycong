<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftRecordsTable extends Migration {
    public function up() {
        Schema::create('shift_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id');
            $table->date('date');
            
            // Ca làm việc: morning, afternoon, evening
            $table->string('shift_type'); 
            
            // Giờ làm thực tế
            $table->decimal('hours', 4, 2)->default(0); 
            
            // Doanh thu của cả CA (dùng chung cho những người cùng làm ca đó)
            $table->decimal('shift_revenue', 15, 2)->default(0);
            
            // Doanh thu cá nhân được hưởng (tính theo công thức Zero-sum)
            $table->decimal('personal_revenue', 15, 2)->default(0);

            // Chỉ tiêu cá nhân phải đạt (Mục 5.1: target_NV)
            $table->decimal('target_amount', 15, 2)->default(0);
            
            // KPI cá nhân đạt được (%)
            $table->decimal('kpi_percentage', 8, 2)->default(0);

            // Số liệu phụ (Secondary metrics)
            $table->integer('customers')->default(0);
            $table->integer('fitting_rooms')->default(0);
            $table->integer('orders')->default(0);
            $table->integer('products')->default(0);
            
            // Trạng thái khóa ngày
            $table->boolean('is_locked')->default(false);

            $table->timestamps();
            
            // Tránh nhập trùng 1 người 1 ca 1 ngày
            $table->unique(['user_id', 'date', 'shift_type']);
        });
    }
    public function down() { Schema::dropIfExists('shift_records'); }
};
