<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\KpiConfig;
use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyController extends Controller
{
    public function index(Request $request)
    {
        $month   = $request->get('month', date('Y-m'));
        $storeId = $request->get('store_id');

        $stores = Store::orderBy('code')->get();

        // ── Tổng quan tất cả stores ──
        $storeOverviews = [];
        $grandTarget    = 0;
        $grandRevenue   = 0;

        foreach ($stores as $store) {
            $config  = KpiConfig::where('store_id', $store->id)->where('month', $month)->first();
            $target  = $config ? (float)$config->total_target : 0;
            $revenue = (float)ShiftRecord::where('store_id', $store->id)
                ->where('date', 'like', "$month%")
                ->sum('personal_revenue');
            $kpiPct  = $target > 0 ? round($revenue / $target * 100, 1) : 0;
            $staffCount = User::where('store_id', $store->id)->where('status', 1)->count();
            $workDays   = ShiftRecord::where('store_id', $store->id)
                ->where('date', 'like', "$month%")
                ->distinct('date')->count('date');

            $grandTarget  += $target;
            $grandRevenue += $revenue;

            $storeOverviews[] = [
                'store'       => $store,
                'target'      => $target,
                'revenue'     => $revenue,
                'kpi_pct'     => $kpiPct,
                'staff_count' => $staffCount,
                'work_days'   => $workDays,
                'has_config'  => (bool)$config,
            ];
        }

        $grandKpi = $grandTarget > 0 ? round($grandRevenue / $grandTarget * 100, 1) : 0;

        return view('monthly.index', compact(
            'month', 'stores',
            'storeOverviews', 'grandTarget', 'grandRevenue', 'grandKpi'
        ));
    }

    public function show(Request $request, \App\Models\Store $store)
    {
        $month = $request->get('month', date('Y-m'));

        $kpiConfig    = KpiConfig::where('store_id', $store->id)->where('month', $month)->first();
        $storeTarget  = $kpiConfig ? (float)$kpiConfig->total_target : 0;
        $storeRevenue = (float)ShiftRecord::where('store_id', $store->id)
            ->where('date', 'like', "$month%")->sum('personal_revenue');
        $storeKpiPct  = $storeTarget > 0 ? round($storeRevenue / $storeTarget * 100, 1) : 0;

        $allShifts   = ShiftRecord::where('store_id', $store->id)->where('date', 'like', "$month%")->get();
        $allDailyKpi = EmployeeDailyKpi::where('store_id', $store->id)->where('date', 'like', "$month%")->get();

        $users = User::with('position')->where('store_id', $store->id)->where('status', 1)->orderBy('full_name')->get();

        $employeeData = [];
        foreach ($users as $user) {
            $userShifts   = $allShifts->where('user_id', $user->id);
            $userDailyKpi = $allDailyKpi->where('user_id', $user->id);

            $totalHours   = (float)$userShifts->sum('hours');
            $totalRevenue = (float)$userShifts->sum('personal_revenue');
            $totalTarget  = (float)$userDailyKpi->sum('target_amount');
            $workDays     = $userShifts->pluck('date')->unique()->count();
            $kpiPct       = $totalTarget > 0 ? round($totalRevenue / $totalTarget * 100, 1) : 0;

            $employeeData[] = [
                'user'          => $user,
                'work_days'     => $workDays,
                'total_hours'   => $totalHours,
                'total_revenue' => $totalRevenue,
                'total_target'  => $totalTarget,
                'kpi_pct'       => $kpiPct,
                'is_sales'      => $user->position && $user->position->is_sales,
            ];
        }

        usort($employeeData, fn($a, $b) => $b['kpi_pct'] <=> $a['kpi_pct']);

        return view('monthly.show', compact(
            'store', 'month', 'employeeData',
            'storeTarget', 'storeRevenue', 'storeKpiPct'
        ));
    }
}
