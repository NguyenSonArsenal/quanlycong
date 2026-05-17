<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Lương/giờ mặc định cho chức danh (dùng làm gợi ý khi tạo NV mới)
            $table->decimal('default_hourly_rate', 10, 0)->default(0)->after('team_bonus_base');
            // Loại hợp đồng mặc định
            $table->string('default_contract_type', 5)->default('CT')->after('default_hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['default_hourly_rate', 'default_contract_type']);
        });
    }
};
