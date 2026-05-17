<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;
use App\Models\Position;
use App\Models\KpiConfig;
use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seed toàn bộ dữ liệu tháng 5/2026 cho K01 (KRIK Thái Hà)
 * Bao gồm:
 *  - KPI config tháng: 3.000.000.000 VND
 *  - Daily targets (phân bổ theo tỷ trọng tuần + ngày)
 *  - ShiftRecord: tất cả nhân viên sales × tất cả ngày (giả lập ~80–95% ngày trong tháng)
 *  - EmployeeDailyKpi: target + KPI% từng NV/ngày
 */
class May2026K01Seeder extends Seeder
{
    const MONTH      = '2026-05';
    const STORE_CODE = 'K01';

    // KPI tháng = 3 tỷ
    const TOTAL_TARGET = 3_000_000_000;

    // Tỷ trọng tuần (5 tuần, sum = 100)
    const WEEK_RATIOS = [1 => 18, 2 => 22, 3 => 22, 4 => 22, 5 => 16];

    // Tỷ trọng ngày trong tuần (1=T2…7=CN, sum = 100)
    const DAY_RATIOS = [1 => 11, 2 => 12, 3 => 13, 4 => 13, 5 => 15, 6 => 18, 7 => 18];

    // Tỷ trọng ca: weekday vs weekend
    const SHIFT_WD  = ['morning' => 10, 'afternoon' => 36, 'evening' => 54];
    const SHIFT_WE  = ['morning' => 12, 'afternoon' => 45, 'evening' => 43];

    public function run()
    {
        $store = Store::where('code', self::STORE_CODE)->first();
        if (!$store) {
            $this->command->error('Không tìm thấy store ' . self::STORE_CODE . '. Hãy chạy DatabaseSeeder trước.');
            return;
        }

        // ── 1. Xoá data cũ của K01 tháng 5/2026 ──────────────────────
        $this->command->info('🗑  Xoá data cũ K01 / 2026-05...');

        ShiftRecord::where('store_id', $store->id)
            ->where('date', 'like', self::MONTH . '%')
            ->delete();

        EmployeeDailyKpi::where('store_id', $store->id)
            ->where('date', 'like', self::MONTH . '%')
            ->delete();

        KpiConfig::where('store_id', $store->id)
            ->where('month', self::MONTH)
            ->forceDelete();

        // ── 2. Tạo KPI Config ─────────────────────────────────────────
        $this->command->info('⚙️  Tạo KPI config...');

        $kpiConfig = KpiConfig::create([
            'store_id'             => $store->id,
            'month'                => self::MONTH,
            'total_target'         => self::TOTAL_TARGET,
            'weekly_ratios'        => self::WEEK_RATIOS,
            'daily_ratios'         => self::DAY_RATIOS,
            'shift_ratios_weekday' => self::SHIFT_WD,
            'shift_ratios_weekend' => self::SHIFT_WE,
        ]);

        // ── 3. Tạo Daily Targets ──────────────────────────────────────
        $this->command->info('📅  Tạo daily targets...');
        $dailyTargets = $this->buildDailyTargets($kpiConfig);

        // ── 4. Load nhân viên sales K01 ───────────────────────────────
        $salesUsers = User::with('position')
            ->where('store_id', $store->id)
            ->where('status', 1)
            ->whereHas('position', fn($q) => $q->where('is_sales', true))
            ->get();

        $allUsers = User::with('position')
            ->where('store_id', $store->id)
            ->where('status', 1)
            ->get();

        if ($salesUsers->isEmpty()) {
            $this->command->error('Không tìm thấy nhân viên sales nào trong K01!');
            return;
        }

        $this->command->info("👥  Tìm thấy {$salesUsers->count()} NV sales, {$allUsers->count()} NV tổng.");

        // ── 5. Sinh shift records cho từng ngày ───────────────────────
        $this->command->info('🕐  Sinh shift records...');

        $shiftRows    = [];
        $kpiRows      = [];
        $now          = now();

        foreach ($dailyTargets as $dateStr => $dayTarget) {
            $carbon    = Carbon::parse($dateStr);
            $isWeekend = $carbon->isoWeekday() >= 6;
            $shiftRatios = $isWeekend ? self::SHIFT_WE : self::SHIFT_WD;
            $ratioSum  = array_sum($shiftRatios);

            // Giả lập: ~90% NV đi làm mỗi ngày (random bỏ 1 người)
            $workingUsers = $allUsers->filter(fn($u) => rand(1, 10) <= 9);
            if ($workingUsers->isEmpty()) {
                $workingUsers = $allUsers; // fallback: tất cả đi làm
            }

            // DT cả ngày: random quanh target (60–130% để tạo variance)
            $dayRevenuePct = rand(60, 130) / 100;
            $dayRevenue    = $dayTarget * $dayRevenuePct;

            // DT theo ca
            $revByCa = [];
            foreach ($shiftRatios as $shift => $ratio) {
                $revByCa[$shift] = $dayRevenue * ($ratio / $ratioSum);
            }

            // Tổng giờ từng ca (chỉ tính NV sales làm ca đó)
            $salesWorking = $workingUsers->filter(fn($u) => $u->position?->is_sales);

            // Phân bổ ca cho NV: mỗi NV làm 1-2 ca
            $userShifts = [];
            foreach ($workingUsers as $user) {
                $isSales = $user->position?->is_sales ?? false;
                if ($isSales) {
                    // NV sales: làm chiều + tối chính, có thể thêm sáng
                    $userShifts[$user->id] = $this->randomSalesShifts($isWeekend);
                } else {
                    // Non-sales: làm 1 ca cố định
                    $userShifts[$user->id] = ['morning' => rand(3,4) + rand(0,1)*0.5];
                }
            }

            // Tính tổng giờ từng ca (chỉ sales)
            $totalHoursByCa = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
            foreach ($salesWorking as $user) {
                foreach (($userShifts[$user->id] ?? []) as $shift => $hours) {
                    $totalHoursByCa[$shift] = ($totalHoursByCa[$shift] ?? 0) + $hours;
                }
            }

            // Tổng giờ ca sales (để tính weight_NV)
            $totalSalesHours = array_sum(array_values(
                collect($userShifts)
                    ->filter(fn($shifts, $uid) => $workingUsers->firstWhere('id', $uid)?->position?->is_sales)
                    ->flatMap(fn($shifts) => array_values($shifts))
                    ->all()
            ));

            foreach ($workingUsers as $user) {
                $isSales     = $user->position?->is_sales ?? false;
                $shifts      = $userShifts[$user->id] ?? [];
                $totalHours  = array_sum($shifts);
                $personalRev = 0;

                if ($isSales && $totalSalesHours > 0) {
                    // personal_revenue = DT ngày × (giờ NV / tổng giờ sales)
                    $personalRev = $dayRevenue * ($totalHours / $totalSalesHours);
                }

                // Tính target cá nhân (weight proportional)
                $targetNV = 0;
                if ($isSales && $totalSalesHours > 0) {
                    $targetNV = $dayTarget * ($totalHours / $totalSalesHours);
                }

                $kpiPct = $targetNV > 0 ? round($personalRev / $targetNV * 100, 2) : 0;

                // Số liệu phụ (SLKH, SLHD, SLSP)
                $soKh  = $isSales ? rand(5, 25) : 0;
                $thuDo = $isSales ? rand(2, 12) : 0;
                $soDon = $isSales ? rand(2, $soKh) : 0;
                $soSp  = $isSales ? rand($soDon, $soDon * 3 + 1) : 0;

                // Tạo ShiftRecord cho từng ca
                foreach ($shifts as $shiftType => $hours) {
                    // DT cá nhân từng ca (proportion theo giờ)
                    $caRev = $isSales && $totalHours > 0 ? $personalRev * ($hours / $totalHours) : 0;
                    $caShiftRev = $revByCa[$shiftType] ?? 0;

                    $shiftRows[] = [
                        'user_id'          => $user->id,
                        'store_id'         => $store->id,
                        'date'             => $dateStr,
                        'shift_type'       => $shiftType,
                        'hours'            => $hours,
                        'shift_revenue'    => round($caShiftRev, 0),
                        'personal_revenue' => round($caRev, 0),
                        'is_locked'        => false,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                // EmployeeDailyKpi (1 row / user / ngày)
                $kpiRows[] = [
                    'user_id'                => $user->id,
                    'store_id'               => $store->id,
                    'date'                   => $dateStr,
                    'target_amount'          => round($targetNV, 2),
                    'kpi_percentage'         => $kpiPct,
                    'total_personal_revenue' => round($personalRev, 2),
                    'customers'              => $soKh,
                    'fitting_rooms'          => $thuDo,
                    'orders'                 => $soDon,
                    'products'               => $soSp,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
            }
        }

        // Bulk insert theo batch 500
        $this->command->info('💾  Insert ' . count($shiftRows) . ' shift records...');
        foreach (array_chunk($shiftRows, 500) as $chunk) {
            DB::table('shift_records')->insert($chunk);
        }

        $this->command->info('💾  Insert ' . count($kpiRows) . ' employee_daily_kpi records...');
        foreach (array_chunk($kpiRows, 500) as $chunk) {
            DB::table('employee_daily_kpi')->insert($chunk);
        }

        // ── Summary ──────────────────────────────────────────────────
        $totalRevActual = collect($shiftRows)->sum('personal_revenue');
        $this->command->info('');
        $this->command->info('✅  Done! Tóm tắt:');
        $this->command->info('   Store       : ' . self::STORE_CODE . ' — KRIK Thái Hà');
        $this->command->info('   Tháng       : ' . self::MONTH);
        $this->command->info('   Target      : ' . number_format(self::TOTAL_TARGET) . ' VND');
        $this->command->info('   DT thực tế  : ' . number_format($totalRevActual) . ' VND');
        $this->command->info('   KPI         : ' . round($totalRevActual / self::TOTAL_TARGET * 100, 1) . '%');
        $this->command->info('   Shift rows  : ' . count($shiftRows));
        $this->command->info('   KPI rows    : ' . count($kpiRows));
        $this->command->info('   Số ngày     : ' . count($dailyTargets));
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Build daily targets từ KPI config.
     * Trả về [date_string => target_amount]
     */
    private function buildDailyTargets(KpiConfig $kpiConfig): array
    {
        $targets    = [];
        $start      = Carbon::parse(self::MONTH . '-01');
        $end        = $start->copy()->endOfMonth();
        $weekRatios = self::WEEK_RATIOS;
        $dayRatios  = self::DAY_RATIOS;

        // Tính week_number (tuần thứ mấy trong tháng, bắt đầu từ 1)
        $current = $start->copy();
        while ($current->lte($end)) {
            $weekNum = (int)ceil($current->day / 7);
            $weekNum = min($weekNum, 5);
            $dow     = $current->isoWeekday(); // 1=Mon…7=Sun

            $weekRatio = $weekRatios[$weekNum] ?? 20;
            $dayRatio  = $dayRatios[$dow] ?? 14;

            // Tổng dayRatio trong tuần (sum = 100 theo config)
            $dayRatioSum = array_sum($dayRatios);

            // target ngày = total_target × (week% / 100) × (day% / daySum)
            $target = self::TOTAL_TARGET
                * ($weekRatio / 100)
                * ($dayRatio / $dayRatioSum);

            $dateStr = $current->format('Y-m-d');

            // Lưu vào daily_targets table
            DB::table('daily_targets')->insert([
                'kpi_config_id'    => $kpiConfig->id,
                'date'             => $dateStr,
                'week_number'      => $weekNum,
                'target_amount'    => round($target, 2),
                'rebalanced_target'=> null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $targets[$dateStr] = $target;
            $current->addDay();
        }

        return $targets;
    }

    /**
     * Random ca làm cho NV sales:
     * - Weekday: thường làm chiều + tối, đôi khi thêm sáng
     * - Weekend: phân bổ đều hơn giữa 3 ca
     */
    private function randomSalesShifts(bool $isWeekend): array
    {
        $shifts = [];

        if ($isWeekend) {
            // Weekend: 3 ca phổ biến hơn
            $pattern = rand(1, 10);
            if ($pattern <= 3) {
                $shifts['morning']   = rand(3, 4) + (rand(0, 1) * 0.5);
                $shifts['afternoon'] = rand(4, 6) + (rand(0, 1) * 0.5);
            } elseif ($pattern <= 7) {
                $shifts['afternoon'] = rand(4, 6) + (rand(0, 1) * 0.5);
                $shifts['evening']   = rand(4, 6) + (rand(0, 1) * 0.5);
            } else {
                $shifts['morning']   = rand(3, 4) + (rand(0, 1) * 0.5);
                $shifts['afternoon'] = rand(4, 5) + (rand(0, 1) * 0.5);
                $shifts['evening']   = rand(3, 5) + (rand(0, 1) * 0.5);
            }
        } else {
            // Weekday: chiều + tối là chủ yếu
            $pattern = rand(1, 10);
            if ($pattern <= 2) {
                $shifts['morning']   = rand(3, 4) + (rand(0, 1) * 0.5);
                $shifts['afternoon'] = rand(4, 6) + (rand(0, 1) * 0.5);
            } elseif ($pattern <= 7) {
                $shifts['afternoon'] = rand(4, 6) + (rand(0, 1) * 0.5);
                $shifts['evening']   = rand(4, 6) + (rand(0, 1) * 0.5);
            } else {
                $shifts['morning']   = rand(3, 4) + (rand(0, 1) * 0.5);
                $shifts['evening']   = rand(4, 6) + (rand(0, 1) * 0.5);
            }
        }

        return $shifts;
    }
}
