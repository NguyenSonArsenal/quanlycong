<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\ShiftRecord;
use App\Models\KpiConfig;
use App\Models\DailyTarget;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', date('Y-m'));
        $storeId = $request->get('store_id');
        
        $stores = Store::orderBy('code')->get();
        $payrollData = [];
        $storeKpiPercentage = 0;

        if ($storeId) {
            // 1. Lấy tất cả bản ghi công trong tháng của store
            $records = ShiftRecord::with(['user.position'])
                ->where('store_id', $storeId)
                ->where('date', 'like', "$month%")
                ->get();

            // 2. Tính KPI tổng của Cửa hàng để tính thưởng Team cho QLCH
            $kpiConfig = KpiConfig::where('store_id', $storeId)->where('month', $month)->first();
            if ($kpiConfig && $kpiConfig->total_target > 0) {
                // Doanh thu thực tế tổng store (DISTINCT theo ngày để tránh trùng lặp nếu record chia nhỏ)
                $totalStoreRevenue = ShiftRecord::where('store_id', $storeId)
                    ->where('date', 'like', "$month%")
                    ->select('date', DB::raw('SUM(DISTINCT shift_revenue) as daily_total'))
                    ->groupBy('date')
                    ->get()
                    ->sum('daily_total');
                
                $storeKpiPercentage = ($totalStoreRevenue / $kpiConfig->total_target) * 100;
            }

            // 3. Tính toán cho từng nhân sự
            $usersInStore = User::with('position')->where('store_id', $storeId)->get();
            
            foreach ($usersInStore as $user) {
                $userRecords = $records->where('user_id', $user->id);
                
                $totalHours = $userRecords->sum('hours');
                $totalRevenue = $userRecords->sum('personal_revenue');
                $totalTarget = $userRecords->sum('target_amount');
                $workDays = $userRecords->pluck('date')->unique()->count();
                
                $personalKpiPct = ($totalTarget > 0) ? ($totalRevenue / $totalTarget * 100) : 0;
                
                // Tra bảng hoa hồng (Bracket 5.3)
                $commissionRate = $this->getCommissionRate($user->position->code ?? '', $personalKpiPct);
                
                $baseSalary = $totalHours * $user->salary_per_hour;
                $commission = ($totalRevenue * $commissionRate) / 100;
                
                // Tính thưởng Team cho QLCH (Mục 5.4)
                $teamBonus = 0;
                if ($user->position && $user->position->code === 'QLCH' && $user->position->team_bonus_base > 0) {
                    if ($storeKpiPercentage >= 100) {
                        $teamBonus = $user->position->team_bonus_base;
                    } elseif ($storeKpiPercentage >= 90) {
                        $teamBonus = $user->position->team_bonus_base * 0.5;
                    }
                }

                $payrollData[] = [
                    'user' => $user,
                    'work_days' => $workDays,
                    'total_hours' => $totalHours,
                    'total_revenue' => $totalRevenue,
                    'kpi_pct' => $personalKpiPct,
                    'comm_rate' => $commissionRate,
                    'base_salary' => $baseSalary,
                    'commission' => $commission,
                    'team_bonus' => $teamBonus,
                    'total_salary' => $baseSalary + $commission + $teamBonus
                ];
            }
        }

        return view('payrolls.index', compact('stores', 'storeId', 'month', 'payrollData', 'storeKpiPercentage'));
    }

    private function getCommissionRate($posCode, $kpiPct)
    {
        // Tra cứu từ bảng commission_brackets
        $bracket = DB::table('commission_brackets')
            ->where('position_code', $posCode)
            ->where('min_kpi', '<=', $kpiPct)
            ->where(function($q) use ($kpiPct) {
                $q->where('max_kpi', '>', $kpiPct)->orWhereNull('max_kpi');
            })
            ->first();
            
        return $bracket ? $bracket->commission_rate : 0;
    }
}
