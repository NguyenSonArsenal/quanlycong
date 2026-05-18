<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use App\Models\KpiConfig;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can(['view_payroll_all', 'view_payroll_store'])) {
                abort(403, '❌ Bạn không có quyền xem bảng lương.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $month   = $request->get('month', date('Y-m'));
        $storeId = $request->get('store_id');
        $search  = $request->get('q', '');

        $authUser = auth()->user();

        // ── 1. Xác định scope storeId theo role (role ưu tiên hơn permission) ──
        if ($authUser->role === 'admin') {
            // Admin: xem tất cả, storeId lấy từ request
        } elseif ($authUser->role === 'area_manager') {
            // Area Manager: chỉ xem store trong khu vực
            if ($storeId) {
                $targetStore = Store::find($storeId);
                if (!$targetStore || $targetStore->area_id !== ($authUser->store ? $authUser->store->area_id : null)) {
                    abort(403, '❌ Bạn không có quyền xem bảng lương cửa hàng này.');
                }
            } else {
                if ($authUser->store) {
                    $storeId = Store::where('area_id', $authUser->store->area_id)->value('id');
                }
            }
        } else {
            // QLCH / CHP / Nhân viên: luôn bị giới hạn về store của mình, bất kể permission
            $storeId = $authUser->store_id;

            // Nhân viên thường (không có view_payroll_store và view_payroll_all): chỉ xem chính mình
            if (!$authUser->can('view_payroll_store') && !$authUser->can('view_payroll_all')) {
                $search = $authUser->username;
            }
        }

        // ── 2. Quyết định danh sách stores trong dropdown (theo role) ──
        if ($authUser->role === 'admin') {
            $stores = Store::orderBy('code')->get();
        } elseif ($authUser->role === 'area_manager') {
            $areaId = $authUser->store ? $authUser->store->area_id : null;
            $stores = Store::where('area_id', $areaId)->orderBy('code')->get();
        } else {
            // QLCH / CHP / Nhân viên: chỉ thấy store của mình
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        }

        $payrollData        = [];
        $storeKpiPercentage = 0;
        $storeTarget        = 0;
        $storeRevenue       = 0;

        if ($storeId) {
            // ── 1. Dữ liệu thô cả tháng ──
            $allShiftRecords = ShiftRecord::where('store_id', $storeId)
                ->where('date', 'like', "$month%")
                ->get();

            $allDailyKpi = EmployeeDailyKpi::where('store_id', $storeId)
                ->where('date', 'like', "$month%")
                ->get();

            // ── 2. KPI tổng cửa hàng ──
            $kpiConfig   = KpiConfig::where('store_id', $storeId)->where('month', $month)->first();
            $storeTarget = $kpiConfig ? (float)$kpiConfig->total_target : 0;
            $storeRevenue= (float)$allShiftRecords->sum('personal_revenue');
            $storeKpiPercentage = $storeTarget > 0 ? ($storeRevenue / $storeTarget * 100) : 0;

            // ── 3. Commission rate theo KPI cửa hàng ──
            $hypotheticalKpi = 95.0;

            // ── 4. Tính lương từng NV ──
            $usersQuery = User::with('position')
                ->where('store_id', $storeId)
                ->where('status', 1)
                ->orderBy('full_name');

            // NV thường chỉ thấy row của mình
            if ($authUser->role === 'staff') {
                $usersQuery->where('id', $authUser->id);
            }

            // Tìm kiếm
            if ($search) {
                $usersQuery->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%$search%")
                      ->orWhere('username', 'like', "%$search%");
                });
            }

            $users = $usersQuery->get();

            foreach ($users as $user) {
                $userShifts   = $allShiftRecords->where('user_id', $user->id);
                $userDailyKpi = $allDailyKpi->where('user_id', $user->id);
                $isSales      = $user->position && $user->position->is_sales;

                // Per-shift hours & DT
                $shiftHours = [
                    'morning'   => (float)$userShifts->where('shift_type', 'morning')->sum('hours'),
                    'afternoon' => (float)$userShifts->where('shift_type', 'afternoon')->sum('hours'),
                    'evening'   => (float)$userShifts->where('shift_type', 'evening')->sum('hours'),
                ];
                $shiftRevenue = [
                    'morning'   => (float)$userShifts->where('shift_type', 'morning')->sum('personal_revenue'),
                    'afternoon' => (float)$userShifts->where('shift_type', 'afternoon')->sum('personal_revenue'),
                    'evening'   => (float)$userShifts->where('shift_type', 'evening')->sum('personal_revenue'),
                ];

                $workDays     = $userShifts->pluck('date')->unique()->count();
                $totalHours   = array_sum($shiftHours);
                $totalRevenue = array_sum($shiftRevenue);

                // KPI cá nhân
                $totalTarget    = (float)$userDailyKpi->sum('target_amount');
                $personalKpiPct = $totalTarget > 0 ? round($totalRevenue / $totalTarget * 100, 1) : 0;

                // Lương cứng = hourlyRate × total_hours
                $hourlyRate = (float)($user->hourly_rate ?? 0);
                $baseSalary = $hourlyRate * $totalHours;

                // Commission = DT × bracket_rate theo % KPI cá nhân
                $commRate        = $isSales ? $this->getCommissionRate($user, $personalKpiPct, $month) : 0;
                $commission      = $totalRevenue * $commRate / 100;

                // Commission giả định nếu cá nhân đạt 100%
                $commRateHypo   = $isSales ? $this->getCommissionRate($user, 100.0, $month) : 0;
                $commissionHypo = $totalRevenue * $commRateHypo / 100;
                $totalSalaryHypo= $baseSalary + $commissionHypo;

                // Thưởng team
                $teamBonus = 0;
                if ($user->position && $user->position->team_bonus_base > 0) {
                    if ($storeKpiPercentage >= 100)     $teamBonus = (float)$user->position->team_bonus_base;
                    elseif ($storeKpiPercentage >= 90)  $teamBonus = (float)$user->position->team_bonus_base * 0.5;
                }

                $totalSalary = $baseSalary + $commission + $teamBonus;

                $payrollData[] = [
                    'user'            => $user,
                    'is_sales'        => $isSales,
                    'hourly_rate'     => $hourlyRate,
                    'work_days'       => $workDays,
                    'shift_hours'     => $shiftHours,
                    'shift_revenue'   => $shiftRevenue,
                    'total_hours'     => $totalHours,
                    'total_revenue'   => $totalRevenue,
                    'total_target'    => $totalTarget,
                    'personal_kpi'    => $personalKpiPct,
                    'store_kpi'       => round($storeKpiPercentage, 1),
                    'comm_rate'       => $commRate,
                    'commission'      => $commission,
                    'base_salary'     => $baseSalary,
                    'team_bonus'      => $teamBonus,
                    'total_salary'    => $totalSalary,
                    'comm_rate_hypo'  => $commRateHypo,
                    'total_hypo'      => $totalSalaryHypo,
                ];
            }
        }

        return view('payrolls.index', compact(
            'stores', 'storeId', 'month', 'payrollData',
            'storeKpiPercentage', 'storeTarget', 'storeRevenue', 'search'
        ));
    }

    /**
     * Tra bảng hoa hồng theo position_code + contract_type + KPI% cá nhân
     */
    private function getCommissionRate(User $user, float $personalKpiPct, string $month): float
    {
        if (!$user->position || !$user->position->is_sales) return 0;
        if ($personalKpiPct < 90) return 0; // <90% = 0%

        $monthStart = Carbon::parse($month . '-01')->toDateString();

        $bracket = DB::table('commission_brackets')
            ->where('position_code', $user->position->code)
            ->where('contract_type', $user->contract_type ?? 'CT')
            ->where('min_kpi', '<=', $personalKpiPct)
            ->where(function ($q) use ($personalKpiPct) {
                $q->where('max_kpi', '>', $personalKpiPct)->orWhereNull('max_kpi');
            })
            ->where('effective_from', '<=', $monthStart)
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $monthStart);
            })
            ->orderBy('min_kpi', 'desc')
            ->first();

        return $bracket ? (float)$bracket->commission_rate : 0;
    }
}
