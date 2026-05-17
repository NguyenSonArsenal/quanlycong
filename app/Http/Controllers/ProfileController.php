<?php

namespace App\Http\Controllers;

use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use App\Models\KpiConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $month    = $request->get('month', date('Y-m'));

        // Admin / manager có thể truyền ?user_id=X để xem bất kỳ NV
        $targetUserId = $request->get('user_id');
        if ($targetUserId && in_array($authUser->role, ['admin', 'store_manager'])) {
            $user = User::with('position', 'store')->findOrFail($targetUserId);
        } else {
            $user = $authUser->load('position', 'store');
        }

        $isViewingOther = $user->id !== $authUser->id;

        // ── Dữ liệu tháng ──
        $shiftRecords = ShiftRecord::where('user_id', $user->id)
            ->where('date', 'like', "$month%")
            ->orderBy('date')
            ->get();

        $dailyKpiRecords = EmployeeDailyKpi::where('user_id', $user->id)
            ->where('date', 'like', "$month%")
            ->get()->keyBy('date');

        // ── Bảng công theo ngày ──
        $dailyData = [];
        foreach ($shiftRecords->groupBy('date') as $date => $records) {
            $dk = $dailyKpiRecords[$date] ?? null;
            $dailyData[$date] = [
                'date'          => $date,
                'dow'           => Carbon::parse($date)->locale('vi')->isoFormat('ddd'),
                'shifts'        => $records->keyBy('shift_type'),
                'total_hours'   => (float)$records->sum('hours'),
                'total_revenue' => (float)$records->sum('personal_revenue'),
                'target'        => $dk ? (float)$dk->target_amount : 0,
                'kpi_pct'       => $dk ? (float)$dk->kpi_percentage : 0,
            ];
        }

        // ── Tổng tháng ──
        $workDays       = $shiftRecords->pluck('date')->unique()->count();
        $totalHours     = (float)$shiftRecords->sum('hours');
        $totalRevenue   = (float)$shiftRecords->sum('personal_revenue');
        $totalTarget    = (float)$dailyKpiRecords->sum('target_amount');
        $personalKpiPct = $totalTarget > 0 ? round($totalRevenue / $totalTarget * 100, 1) : 0;

        // ── Tính lương (spec 5.4) ──
        $hourlyRate = (float)($user->hourly_rate ?? 0);
        $baseSalary = $hourlyRate * $totalHours;

        $commRate   = ($user->position && $user->position->is_sales)
            ? $this->getCommissionRate($user, $personalKpiPct, $month)
            : 0;
        $commission = $totalRevenue * $commRate / 100;

        $teamBonus  = 0;
        if ($user->store_id && $user->position && $user->position->team_bonus_base > 0) {
            $storeKpi = $this->getStoreKpi((int)$user->store_id, $month);
            if ($storeKpi >= 100)     $teamBonus = (float)$user->position->team_bonus_base;
            elseif ($storeKpi >= 90)  $teamBonus = (float)$user->position->team_bonus_base * 0.5;
        }

        $totalSalary = $baseSalary + $commission + $teamBonus;

        return view('profile.index', compact(
            'user', 'month', 'dailyData', 'isViewingOther',
            'workDays', 'totalHours', 'totalRevenue', 'totalTarget',
            'personalKpiPct', 'hourlyRate', 'commRate',
            'baseSalary', 'commission', 'teamBonus', 'totalSalary'
        ));
    }

    private function getCommissionRate($user, float $kpiPct, string $month): float
    {
        if (!$user->position || !$user->position->is_sales) return 0;
        if ($kpiPct < 90) return 0;

        $monthStart = Carbon::parse($month . '-01')->toDateString();
        $bracket = DB::table('commission_brackets')
            ->where('position_code', $user->position->code)
            ->where('contract_type', $user->contract_type ?? 'CT')
            ->where('min_kpi', '<=', $kpiPct)
            ->where(function ($q) use ($kpiPct) {
                $q->where('max_kpi', '>', $kpiPct)->orWhereNull('max_kpi');
            })
            ->where('effective_from', '<=', $monthStart)
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $monthStart);
            })
            ->orderBy('min_kpi', 'desc')
            ->first();

        return $bracket ? (float)$bracket->commission_rate : 0;
    }

    private function getStoreKpi(int $storeId, string $month): float
    {
        $kpiConfig = KpiConfig::where('store_id', $storeId)->where('month', $month)->first();
        if (!$kpiConfig || $kpiConfig->total_target <= 0) return 0;
        $revenue = ShiftRecord::where('store_id', $storeId)
            ->where('date', 'like', "$month%")
            ->sum('personal_revenue');
        return round($revenue / $kpiConfig->total_target * 100, 1);
    }
}
