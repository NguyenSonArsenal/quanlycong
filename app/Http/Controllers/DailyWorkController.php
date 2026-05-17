<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\KpiConfig;
use App\Models\DailyTarget;
use App\Models\ShiftRecord;
use App\Models\EmployeeDailyKpi;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyWorkController extends Controller
{
    // ── Hằng: các field thuộc employee_daily_kpi (không phải shift_records) ──
    const KPI_FIELDS = ['customers', 'fitting_rooms', 'orders', 'products'];

    public function index(Request $request)
    {
        $date    = $request->get('date', date('Y-m-d'));
        $storeId = $request->get('store_id');
        $stores  = Store::orderBy('code')->get();

        if (auth()->user()->role === 'store_manager' && !$storeId) {
            $storeId = auth()->user()->store_id;
        }

        $users       = collect();
        $dailyTarget = null;
        $isLocked    = false;
        $kpiData     = [];
        $totals      = [];

        if ($storeId) {
            $users = User::with('position')
                ->where('store_id', $storeId)
                ->where('status', 1)
                ->orderBy('full_name')
                ->get();

            $monthStr    = Carbon::parse($date)->format('Y-m');
            $dailyTarget = DailyTarget::whereHas('kpiConfig', fn($q) =>
                $q->where('store_id', $storeId)->where('month', $monthStr)
            )->where('date', $date)->first();

            $isLocked    = ShiftRecord::where('store_id', $storeId)->where('date', $date)->where('is_locked', true)->exists();
            $userRecords = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();

            // Load employee_daily_kpi cho ngày này
            $dailyKpiRecords = EmployeeDailyKpi::where('store_id', $storeId)->where('date', $date)->get()->keyBy('user_id');

            foreach ($users as $user) {
                $user->shifts    = $userRecords->where('user_id', $user->id)->keyBy('shift_type');
                $user->daily_kpi = $dailyKpiRecords[$user->id] ?? null;

                $totalRev = $user->shifts->sum('personal_revenue');
                $target   = $user->daily_kpi ? (float)$user->daily_kpi->target_amount : 0;
                $kpiPct   = $target > 0 ? round($totalRev / $target * 100, 1) : 0;
                $kpiData[$user->id] = compact('totalRev', 'target', 'kpiPct');
            }

            $totals = $this->getDayTotals($storeId, $date);
        }

        return view('daily.index', compact(
            'stores', 'storeId', 'date', 'users',
            'dailyTarget', 'isLocked', 'kpiData', 'totals'
        ));
    }

    // ── Save-on-blur ──
    public function updateField(Request $request)
    {
        $request->validate([
            'user_id'    => 'required',
            'store_id'   => 'required',
            'date'       => 'required',
            'shift_type' => 'required',
            'field'      => 'required',
        ]);

        $field = $request->field;
        $val   = $request->value ?? 0;

        if ($field === 'hours' && $val > 0) {
            $val = (float)$val;
            if ($val < 0.5 || $val > 6 || fmod($val, 0.5) != 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Giờ công mỗi ca phải từ 0.5 đến 6 và chia hết cho 0.5!'
                ], 422);
            }
        }

        if (in_array($field, self::KPI_FIELDS)) {
            // Số liệu phụ → lưu vào employee_daily_kpi
            EmployeeDailyKpi::updateOrCreate(
                ['user_id' => $request->user_id, 'store_id' => $request->store_id, 'date' => $request->date],
                [$field => $val]
            );
        } elseif ($field === 'hours' && (!$val || $val <= 0)) {
            // Giờ = 0 hoặc trống → XÓA hẳn bản ghi ca đó (kéo theo personal_revenue)
            ShiftRecord::where('user_id', $request->user_id)
                ->where('store_id', $request->store_id)
                ->where('date', $request->date)
                ->where('shift_type', $request->shift_type)
                ->delete();
        } else {
            // Giờ công / DT cá nhân → lưu vào shift_records
            ShiftRecord::updateOrCreate(
                ['user_id' => $request->user_id, 'date' => $request->date, 'shift_type' => $request->shift_type],
                ['store_id' => $request->store_id, $field => $val]
            );
        }

        $allKpi = [];
        if (in_array($field, ['personal_revenue', 'hours'])) {
            $allKpi = $this->recalculateTargets($request->store_id, $request->date);
        }

        return response()->json([
            'status'  => 'success',
            'all_kpi' => $allKpi,
            'totals'  => $this->getDayTotals($request->store_id, $request->date),
        ]);
    }

    // ── Xóa toàn bộ dữ liệu 1 NV trong ngày ──
    public function deleteRecord(Request $request, $userId)
    {
        ShiftRecord::where('user_id', $userId)
            ->where('store_id', $request->store_id)
            ->where('date', $request->date)
            ->where('is_locked', false)
            ->delete();

        EmployeeDailyKpi::where('user_id', $userId)
            ->where('store_id', $request->store_id)
            ->where('date', $request->date)
            ->delete();

        return response()->json(['status' => 'success']);
    }

    // ── Cân bằng KPI ──
    public function equalize(Request $request)
    {
        $date         = $request->date;
        $storeId      = $request->store_id;
        $totalRevenue = (float)($request->total_revenue ?? 0);

        DB::beginTransaction();
        try {
            $ratios  = $this->getShiftRatios($storeId, $date);
            $records = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
            $users   = User::with('position')->where('store_id', $storeId)->get()->keyBy('id');

            $totalHours = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
            foreach ($records as $rec) {
                $u = $users[$rec->user_id] ?? null;
                if ($u && $u->position && $u->position->is_sales) {
                    $totalHours[$rec->shift_type] = ($totalHours[$rec->shift_type] ?? 0) + $rec->hours;
                }
            }

            foreach ($records as $rec) {
                $u = $users[$rec->user_id] ?? null;
                if ($u && $u->position && $u->position->is_sales && ($totalHours[$rec->shift_type] ?? 0) > 0) {
                    $shiftRev              = $totalRevenue * ($ratios[$rec->shift_type] ?? 0);
                    $rec->personal_revenue = ($rec->hours / $totalHours[$rec->shift_type]) * $shiftRev;
                    $rec->shift_revenue    = $shiftRev;
                    $rec->save();
                }
            }

            $this->recalculateTargets($storeId, $date);
            $this->rebalanceWeekly($storeId, $date, $totalRevenue);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Đã cân bằng KPI!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ── Khóa ngày → tự động rebalance KPI các ngày còn lại trong tuần ──
    public function lock(Request $request)
    {
        $storeId = $request->store_id;
        $date    = $request->date;

        // 1. Khóa toàn bộ shift records của ngày
        ShiftRecord::where('store_id', $storeId)
            ->where('date', $date)
            ->update(['is_locked' => true]);

        // 2. Lấy tổng doanh thu thực tế hôm nay từ shift_records
        $actualRevenue = ShiftRecord::where('store_id', $storeId)
            ->where('date', $date)
            ->sum('personal_revenue');

        // 3. Tái phân bổ KPI các ngày còn lại trong tuần
        $this->rebalanceWeekly($storeId, $date, $actualRevenue);

        return response()->json([
            'status'  => 'success',
            'message' => 'Đã khóa ngày và tái phân bổ KPI tuần.',
            'actual_revenue' => $actualRevenue,
        ]);
    }

    // ─────────── Private helpers ───────────

    private function getDayTotals($storeId, $date): array
    {
        $shiftRecs  = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
        $kpiRecs    = EmployeeDailyKpi::where('store_id', $storeId)->where('date', $date)->get();
        return [
            'store_revenue' => $shiftRecs->sum('personal_revenue'),
            'customers'     => $kpiRecs->sum('customers'),
            'fitting_rooms' => $kpiRecs->sum('fitting_rooms'),
            'orders'        => $kpiRecs->sum('orders'),
            'products'      => $kpiRecs->sum('products'),
        ];
    }

    private function getShiftRatios($storeId, $date): array
    {
        $monthStr  = Carbon::parse($date)->format('Y-m');
        $kpiConfig = KpiConfig::where('store_id', $storeId)->where('month', $monthStr)->first();
        $raw = $kpiConfig
            ? $kpiConfig->getShiftRatioForDate($date)
            : (Carbon::parse($date)->isoWeekday() >= 6
                ? ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
                : ['morning' => 10, 'afternoon' => 36, 'evening' => 54]);

        $total = array_sum($raw);
        return array_map(fn($r) => $total > 0 ? $r / $total : 0, $raw);
    }

    private function recalculateTargets($storeId, $date): array
    {
        $monthStr    = Carbon::parse($date)->format('Y-m');
        $dailyTarget = DailyTarget::whereHas('kpiConfig', fn($q) =>
            $q->where('store_id', $storeId)->where('month', $monthStr)
        )->where('date', $date)->first();

        $kpiNgay = $dailyTarget ? (float)($dailyTarget->rebalanced_target ?: $dailyTarget->target_amount) : 0;
        $ratios  = $this->getShiftRatios($storeId, $date);
        $records = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
        $users   = User::with('position')->where('store_id', $storeId)->get()->keyBy('id');

        $totalH = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
        foreach ($records as $rec) {
            $u = $users[$rec->user_id] ?? null;
            if ($u && $u->position && $u->position->is_sales) {
                $totalH[$rec->shift_type] = ($totalH[$rec->shift_type] ?? 0) + $rec->hours;
            }
        }

        // ── wDS Rescaling: redistribute ratio của ca không có ai làm ──
        // Tương tự logic wDS trong KPI engine (DailyTarget generation)
        // VD: chỉ có chiều (36%) có người → rescale: chiều = 36/36 = 100%
        $effectiveRatioSum = 0;
        foreach ($totalH as $shift => $hours) {
            if ($hours > 0) {
                $effectiveRatioSum += $ratios[$shift] ?? 0;
            }
        }
        $rescaledRatios = [];
        foreach ($ratios as $shift => $r) {
            $rescaledRatios[$shift] = ($effectiveRatioSum > 0 && ($totalH[$shift] ?? 0) > 0)
                ? $r / $effectiveRatioSum
                : 0;
        }

        $result = [];
        foreach ($records->groupBy('user_id') as $userId => $userShifts) {
            $u = $users[$userId] ?? null;
            if (!$u || !$u->position || !$u->position->is_sales) continue;

            // target_NV = Σ (giờ_NV_ca / tổng_giờ_ca) × (KPI_ngày × rescaled_ratio_ca)
            $targetUser = 0;
            foreach ($userShifts as $sr) {
                $h = $totalH[$sr->shift_type] ?? 0;
                if ($h > 0 && $sr->hours > 0) {
                    $targetUser += ($sr->hours / $h) * ($kpiNgay * ($rescaledRatios[$sr->shift_type] ?? 0));
                }
            }

            $totalRev = $userShifts->sum('personal_revenue');
            $kpiPct   = $targetUser > 0 ? round($totalRev / $targetUser * 100, 2) : 0;

            // Lưu vào employee_daily_kpi (bảng đúng) ──
            EmployeeDailyKpi::updateOrCreate(
                ['user_id' => $userId, 'store_id' => $storeId, 'date' => $date],
                [
                    'target_amount'         => round($targetUser, 2),
                    'kpi_percentage'        => $kpiPct,
                    'total_personal_revenue'=> round($totalRev, 2),
                ]
            );

            $result[$userId] = [
                'user_id'   => $userId,
                'total_rev' => (float)$totalRev,
                'target'    => round($targetUser, 2),
                'kpi_pct'   => $targetUser > 0 ? round($totalRev / $targetUser * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function rebalanceWeekly($storeId, $date, $actualRevenue)
    {
        $carbonDate = Carbon::parse($date);
        $weekStart  = $carbonDate->copy()->startOfWeek()->toDateString();
        $weekEnd    = $carbonDate->copy()->endOfWeek()->toDateString();

        // Lấy KpiConfig để đọc daily_ratios thực tế từ DB
        $kpiConfig = \App\Models\KpiConfig::where('store_id', $storeId)
            ->where('month', $carbonDate->format('Y-m'))
            ->first();

        // Lấy daily_ratios: {1: 40, 2: 40, ..., 5: 60, 6: 60, 7: 60}
        // key = isoWeekday (1=T2...7=CN), value = trọng số ngày đó
        $dailyRatios = [];
        foreach (($kpiConfig->daily_ratios ?? []) as $k => $v) {
            $dailyRatios[(int)$k] = (float)$v;
        }

        // Chỉ lấy daily targets trong tuần hiện tại
        $weeklyTargets = DailyTarget::where('kpi_config_id', $kpiConfig->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $weeklyTotal = $weeklyTargets->sum('target_amount');

        // Tính DT thực tế từ đầu tuần đến ngày bị khóa (personal_revenue vì NV nhập tay)
        $pastActual = ShiftRecord::where('store_id', $storeId)
            ->whereBetween('date', [$weekStart, $date])
            ->sum('personal_revenue');

        // KPI còn lại cần đạt trong các ngày tới
        $remaining  = max(0, $weeklyTotal - $pastActual);
        $futureDays = $weeklyTargets->where('date', '>', $date);

        if ($futureDays->count() > 0) {
            // Tổng trọng số các ngày còn lại, lấy từ daily_ratios trong config
            $totalW = $futureDays->sum(function ($d) use ($dailyRatios) {
                $dow = Carbon::parse($d->date)->isoWeekday();
                return $dailyRatios[$dow] ?? 1.0;
            });

            foreach ($futureDays as $ft) {
                $dow = Carbon::parse($ft->date)->isoWeekday();
                $w   = $dailyRatios[$dow] ?? 1.0;
                $ft->rebalanced_target = $totalW > 0 ? round($remaining * $w / $totalW, 2) : 0;
                $ft->save();
            }
        }
    }
}
