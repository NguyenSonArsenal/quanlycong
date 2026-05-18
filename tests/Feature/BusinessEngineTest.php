<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\KpiConfig;
use App\Models\DailyTarget;
use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusinessEngineTest extends TestCase
{
    use DatabaseTransactions; // Tự động rollback sau khi test xong để không làm bẩn DB chính!

    private $store;
    private $salesPosition;
    private $managerPosition;
    private $salesUser1;
    private $salesUser2;
    private $managerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bỏ qua CSRF Token verification khi chạy unit/integration tests
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // 1. Tạo Cửa hàng giả lập phục vụ test
        $this->store = Store::create([
            'code' => 'TEST_K99',
            'name' => 'Cửa hàng Test K99',
            'address' => '123 Đường Test, Hà Nội'
        ]);

        // 2. Lấy hoặc tạo Chức danh
        $this->salesPosition = Position::firstOrCreate(
            ['code' => 'NVBH_FT'],
            ['name' => 'Nhân viên bán hàng Full-time', 'is_sales' => true]
        );

        $this->managerPosition = Position::firstOrCreate(
            ['code' => 'QLCH'],
            ['name' => 'Quản lý cửa hàng', 'is_sales' => false, 'team_bonus_base' => 5000000]
        );

        // 3. Tạo nhân viên mẫu
        $this->salesUser1 = User::create([
            'username' => 'test_sales_ft1',
            'password' => bcrypt('password'),
            'full_name' => 'Nguyễn Văn Sales Một',
            'role' => 'staff',
            'store_id' => $this->store->id,
            'position_id' => $this->salesPosition->id,
            'contract_type' => 'CT',
            'hourly_rate' => 35000,
            'status' => 1
        ]);

        $this->salesUser2 = User::create([
            'username' => 'test_sales_ft2',
            'password' => bcrypt('password'),
            'full_name' => 'Trần Thị Sales Hai',
            'role' => 'staff',
            'store_id' => $this->store->id,
            'position_id' => $this->salesPosition->id,
            'contract_type' => 'CT',
            'hourly_rate' => 35000,
            'status' => 1
        ]);

        $this->managerUser = User::create([
            'username' => 'test_manager_k99',
            'password' => bcrypt('password'),
            'full_name' => 'Phạm Hoàng Quản Lý',
            'role' => 'store_manager',
            'store_id' => $this->store->id,
            'position_id' => $this->managerPosition->id,
            'contract_type' => 'CT',
            'hourly_rate' => 0,
            'status' => 1
        ]);
    }

    /**
     * ── FORMULA UNIT TEST 1: generateDailyTargets ──
     * Kiểm tra việc phân bổ chỉ tiêu (KPI) tháng xuống từng ngày theo trọng số tuần và ngày.
     */
    public function test_formula_1_generate_daily_targets()
    {
        // Setup KPI config tháng 05/2026 tổng chỉ tiêu 100,000,000đ
        $config = KpiConfig::create([
            'store_id' => $this->store->id,
            'month' => '2026-05',
            'total_target' => 100000000,
            'weekly_ratios' => [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20],
            'daily_ratios' => [
                1 => 10, 2 => 10, 3 => 10, 4 => 10, // Thứ 2-5 (early): 10%
                5 => 15, 6 => 15, 7 => 15          // Thứ 6-CN (late): 15%
            ],
            'shift_ratios_weekday' => ['morning' => 10, 'afternoon' => 36, 'evening' => 54],
            'shift_ratios_weekend' => ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
        ]);

        // Kích hoạt sinh chỉ tiêu ngày
        $controller = new \App\Http\Controllers\KpiController();
        $refMethod = new \ReflectionMethod($controller, 'generateDailyTargets');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $config);

        // Lấy kết quả từ database
        $dailyTargets = DailyTarget::where('kpi_config_id', $config->id)->orderBy('date')->get();

        // Xác nhận số ngày tháng 5 phải là 31 ngày
        $this->assertEquals(31, $dailyTargets->count());

        // Tổng chỉ tiêu của các ngày được phân bổ phải khớp với tổng chỉ tiêu tháng (sai số nhỏ do làm tròn)
        $totalSum = $dailyTargets->sum('target_amount');
        $this->assertEquals(100000000, round($totalSum, 0));

        // Kiểm tra chi tiết 1 ngày trong tuần (T2: 2026-05-04 là thứ 2 thuộc tuần 2) và cuối tuần (CN: 2026-05-10 là CN thuộc tuần 2)
        // Ngày trong tuần (dow: 1..4) có trọng số 10. Cuối tuần (dow: 5..7) có trọng số 15.
        // Tỷ lệ target cuối tuần / ngày trong tuần phải là 15 / 10 = 1.5 lần!
        $t2Target = $dailyTargets->where('date', '2026-05-04')->first()->target_amount;
        $cnTarget = $dailyTargets->where('date', '2026-05-10')->first()->target_amount;

        $this->assertEquals(1.5, round($cnTarget / $t2Target, 1));
    }

    /**
     * ── FORMULA UNIT TEST 2: equalize (Zero-sum personal revenue distribution) ──
     * Kiểm tra công thức chia doanh thu ca cho nhân sự bán hàng theo số giờ làm thực tế.
     */
    public function test_formula_2_zero_sum_equalize()
    {
        // 1. Setup config KPI
        $config = KpiConfig::create([
            'store_id' => $this->store->id,
            'month' => '2026-05',
            'total_target' => 50000000,
            'weekly_ratios' => [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20],
            'daily_ratios' => [1 => 10, 2 => 10, 3 => 10, 4 => 10, 5 => 15, 6 => 15, 7 => 15],
            'shift_ratios_weekday' => ['morning' => 10, 'afternoon' => 40, 'evening' => 50],
            'shift_ratios_weekend' => ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
        ]);

        // 2. Setup 2 Sales cùng làm ca Sáng ngày 2026-05-18 (Thứ 2)
        // Sales 1 làm 4 tiếng, Sales 2 làm 6 tiếng (tổng cộng 10 tiếng)
        ShiftRecord::create([
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'hours' => 4.0
        ]);

        ShiftRecord::create([
            'user_id' => $this->salesUser2->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'hours' => 6.0
        ]);

        // Giả sử tổng doanh thu ngày đạt được là 20,000,000đ.
        // Ca sáng chiếm tỷ lệ 10% trong ngày thứ 2 => Doanh thu ca Sáng = 20,000,000đ * 10% = 2,000,000đ.
        // Sales 1 được chia: (4 / 10) * 2,000,000đ = 800,000đ.
        // Sales 2 được chia: (6 / 10) * 2,000,000đ = 1,200,000đ.
        $controller = new \App\Http\Controllers\DailyWorkController();
        $request = new \Illuminate\Http\Request([
            'date' => '2026-05-18',
            'store_id' => $this->store->id,
            'total_revenue' => 20000000
        ]);

        $response = $controller->equalize($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Lấy kết quả từ DB kiểm tra
        $rec1 = ShiftRecord::where('user_id', $this->salesUser1->id)->where('date', '2026-05-18')->first();
        $rec2 = ShiftRecord::where('user_id', $this->salesUser2->id)->where('date', '2026-05-18')->first();

        $this->assertEquals(800000, (float)$rec1->personal_revenue);
        $this->assertEquals(1200000, (float)$rec2->personal_revenue);
        $this->assertEquals(2000000, (float)$rec1->shift_revenue);
    }

    /**
     * ── FORMULA UNIT TEST 3: recalculateTargets (wDS Rescaling Target) ──
     * Kiểm tra việc tính chỉ tiêu cá nhân đạt được (target_NV) dựa trên giờ làm của ca thực tế và rescaling wDS.
     */
    public function test_formula_3_rescaled_daily_kpi_targets()
    {
        // 1. Tạo DailyTarget mẫu cho ngày 2026-05-18 trị giá 5,000,000đ
        $config = KpiConfig::create([
            'store_id' => $this->store->id,
            'month' => '2026-05',
            'total_target' => 50000000,
            'weekly_ratios' => [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20],
            'daily_ratios' => [1 => 10, 2 => 10, 3 => 10, 4 => 10, 5 => 15, 6 => 15, 7 => 15],
            'shift_ratios_weekday' => ['morning' => 10, 'afternoon' => 40, 'evening' => 50],
            'shift_ratios_weekend' => ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
        ]);

        DailyTarget::create([
            'kpi_config_id' => $config->id,
            'date' => '2026-05-18',
            'week_number' => 2,
            'target_amount' => 5000000,
            'rebalanced_target' => 5000000
        ]);

        // 2. Setup công làm việc: chỉ có Sales 1 làm ca sáng (4 tiếng) và ca chiều (4 tiếng). Ca tối không có ai làm.
        // Tỷ trọng gốc: Sáng = 10%, Chiều = 40%, Tối = 50%.
        // Do ca tối trống người làm => wDS Rescaling chỉ còn Sáng và Chiều gánh vác:
        // Tỷ lệ rescaling: Sáng = 10 / (10 + 40) = 20%. Chiều = 40 / (10 + 40) = 80%.
        // Target cá nhân Sales 1:
        // Ca Sáng: (4h / 4h) * (5,000,000đ * 20%) = 1,000,000đ.
        // Ca Chiều: (4h / 4h) * (5,000,000đ * 80%) = 4,000,000đ.
        // Tổng chỉ tiêu cá nhân Sales 1 gánh = 5,000,000đ!
        ShiftRecord::create([
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'hours' => 4.0,
            'personal_revenue' => 1200000 // doanh thu cá nhân Sales 1 đạt 1.2M
        ]);

        ShiftRecord::create([
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'afternoon',
            'hours' => 4.0,
            'personal_revenue' => 3800000 // doanh thu cá nhân Sales 1 đạt 3.8M
        ]);

        // Thực thi việc tính toán
        $controller = new \App\Http\Controllers\DailyWorkController();
        $refMethod = new \ReflectionMethod($controller, 'recalculateTargets');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $this->store->id, '2026-05-18');

        // Xác nhận KPI của Sales 1 được lưu đúng
        $dailyKpi = EmployeeDailyKpi::where('user_id', $this->salesUser1->id)->where('date', '2026-05-18')->first();

        // Chỉ tiêu gánh vác phải là đúng 5,000,000đ
        $this->assertEquals(5000000, (float)$dailyKpi->target_amount);

        // Doanh thu đạt được 1.2M + 3.8M = 5,000,000đ => Đạt đúng 100% KPI cá nhân!
        $this->assertEquals(100.0, (float)$dailyKpi->kpi_percentage);
    }

    /**
     * ── FORMULA UNIT TEST 4: rebalanceWeekly (Target Weekly Rebalancing) ──
     * Kiểm tra công thức tự động dàn đều chỉ tiêu chưa đạt từ các ngày đã qua sang các ngày còn lại trong tuần,
     * nhân thêm trọng số cuối tuần 1.5x cho thứ Bảy và Chủ Nhật.
     */
    public function test_formula_4_weekly_target_rebalancing()
    {
        // 1. Tạo config và daily target của 1 tuần (từ Thứ 2 đến Chủ Nhật)
        $config = KpiConfig::create([
            'store_id' => $this->store->id,
            'month' => '2026-05',
            'total_target' => 70000000,
            'weekly_ratios' => [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20],
            'daily_ratios' => [1 => 10, 2 => 10, 3 => 10, 4 => 10, 5 => 10, 6 => 15, 7 => 15],
            'shift_ratios_weekday' => ['morning' => 10, 'afternoon' => 40, 'evening' => 50],
            'shift_ratios_weekend' => ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
        ]);

        // Tạo targets cho 7 ngày của tuần 2 tháng 05/2026 (Từ Thứ 2 11/05 đến CN 17/05)
        // Mỗi ngày ban đầu chỉ tiêu 2,000,000đ => Tổng cả tuần = 14,000,000đ.
        $dates = [
            '2026-05-11', '2026-05-12', '2026-05-13', '2026-05-14', '2026-05-15', '2026-05-16', '2026-05-17'
        ];
        foreach ($dates as $d) {
            DailyTarget::create([
                'kpi_config_id' => $config->id,
                'date' => $d,
                'week_number' => 2,
                'target_amount' => 2000000,
                'rebalanced_target' => 2000000
            ]);
        }

        // 2. Ghi nhận doanh thu thực tế cho Thứ 2 (11/05) và Thứ 3 (12/05):
        // Chỉ tiêu T2 + T3 = 4,000,000đ. Nhưng thực tế 2 ngày này chỉ đạt được 1,000,000đ.
        // Hụt 3,000,000đ sẽ được phân bổ lại vào 5 ngày còn lại: Thứ 4, 5, 6, Bảy, CN.
        // Tổng chỉ tiêu tuần còn lại cần phân bổ: (Tổng tuần 14,000,000đ) - (Đã đạt 1,000,000đ) = 13,000,000đ.
        // Các ngày còn lại: T4, T5, T6 (trọng số 1.0), T7, CN (trọng số 1.5).
        // Tổng trọng số còn lại = 1 + 1 + 1 + 1.5 + 1.5 = 6.0.
        // Chỉ tiêu mới của ngày thường (T4, T5, T6): 13,000,000đ * 1.0 / 6.0 = 2,166,666.67đ.
        // Chỉ tiêu mới của cuối tuần (T7, CN): 13,000,000đ * 1.5 / 6.0 = 3,250,000đ.
        ShiftRecord::create([
            'user_id' => $this->salesUser1->id, 'store_id' => $this->store->id, 'date' => '2026-05-11',
            'shift_type' => 'morning', 'hours' => 8.0, 'personal_revenue' => 500000, 'shift_revenue' => 500000
        ]);
        ShiftRecord::create([
            'user_id' => $this->salesUser1->id, 'store_id' => $this->store->id, 'date' => '2026-05-12',
            'shift_type' => 'morning', 'hours' => 8.0, 'personal_revenue' => 500000, 'shift_revenue' => 500000
        ]);

        // Kích hoạt Rebalance từ Thứ 3 (12/05) với doanh thu ngày là 500,000đ
        $controller = new \App\Http\Controllers\DailyWorkController();
        $refMethod = new \ReflectionMethod($controller, 'rebalanceWeekly');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $this->store->id, '2026-05-12', 500000);

        // Lấy lại các target đã được tái phân bổ từ DB
        $t4 = DailyTarget::where('kpi_config_id', $config->id)->where('date', '2026-05-13')->first()->rebalanced_target;
        $t7 = DailyTarget::where('kpi_config_id', $config->id)->where('date', '2026-05-16')->first()->rebalanced_target;

        // Xác nhận khớp chuẩn xác theo công thức tỷ trọng
        $this->assertEquals(2166666.67, round($t4, 2));
        $this->assertEquals(3250000, round($t7, 2));
    }

    /**
     * ── INTEGRATION TEST: Luồng Nhập công + Khóa ngày ──
     * Test luồng tích hợp thực tế: Store Manager đăng nhập ➔ Nhập giờ công và phụ lục ➔ Khóa ngày ➔ Ngăn chặn cập nhật.
     */
    public function test_integration_flow_work_entry_and_locking()
    {
        // Giả lập đăng nhập dưới quyền Quản lý cửa hàng (Store Manager)
        $this->actingAs($this->managerUser);

        // Bước 1: Quản lý lưu giờ làm của nhân viên (Sales 1 làm ca sáng 5 tiếng)
        $response = $this->postJson(route('fe.daily.update'), [
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'field' => 'hours',
            'value' => 5.0
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Xác nhận bản ghi giờ làm đã được lưu vào shift_records
        $this->assertDatabaseHas('shift_records', [
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'hours' => 5.0
        ]);

        // Bước 2: Quản lý nhập số liệu phụ (số khách hàng thử đồ)
        $responseKpi = $this->postJson(route('fe.daily.update'), [
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'shift_type' => 'morning',
            'field' => 'customers',
            'value' => 12
        ]);

        $responseKpi->assertStatus(200);

        // Xác nhận số liệu phụ đã được lưu vào bảng employee_daily_kpi
        $this->assertDatabaseHas('employee_daily_kpi', [
            'user_id' => $this->salesUser1->id,
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'customers' => 12
        ]);

        // Bước 3: Thực hiện Khóa ngày (Lock Day)
        $responseLock = $this->postJson(route('fe.daily.lock'), [
            'store_id' => $this->store->id,
            'date' => '2026-05-18'
        ]);

        $responseLock->assertStatus(200);
        $responseLock->assertJsonPath('status', 'success');

        // Xác nhận trạng thái khóa đã được cập nhật thành true
        $this->assertDatabaseHas('shift_records', [
            'store_id' => $this->store->id,
            'date' => '2026-05-18',
            'is_locked' => true
        ]);
    }
}
