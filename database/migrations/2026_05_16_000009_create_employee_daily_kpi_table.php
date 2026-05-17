<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng employee_daily_kpi
        Schema::create('employee_daily_kpi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id');
            $table->date('date');

            // KPI tổng hợp cả ngày
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->decimal('kpi_percentage', 8, 2)->default(0);
            $table->decimal('total_personal_revenue', 15, 2)->default(0);

            // Số liệu phụ (per ngày per NV, không phải per ca)
            $table->integer('customers')->default(0);
            $table->integer('fitting_rooms')->default(0);
            $table->integer('orders')->default(0);
            $table->integer('products')->default(0);

            $table->timestamps();
            $table->unique(['user_id', 'date']); // 1 NV chỉ 1 row / ngày
        });

        // 2. Xóa các cột đã chuyển sang bảng mới khỏi shift_records
        Schema::table('shift_records', function (Blueprint $table) {
            $table->dropColumn([
                'target_amount',
                'kpi_percentage',
                'customers',
                'fitting_rooms',
                'orders',
                'products',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_daily_kpi');

        Schema::table('shift_records', function (Blueprint $table) {
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->decimal('kpi_percentage', 8, 2)->default(0);
            $table->integer('customers')->default(0);
            $table->integer('fitting_rooms')->default(0);
            $table->integer('orders')->default(0);
            $table->integer('products')->default(0);
        });
    }
};
