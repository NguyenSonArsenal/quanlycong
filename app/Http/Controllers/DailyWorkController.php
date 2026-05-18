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
        $date     = $request->get('date', date('Y-m-d'));
        $storeId  = $request->get('store_id');
        $authUser = auth()->user();

        // ── Giới hạn danh sách stores trong dropdown theo vai trò ──
        if ($authUser && $authUser->role === 'area_manager') {
            $stores = Store::where('area_id', $authUser->store ? $authUser->store->area_id : null)->orderBy('code')->get();
        } elseif ($authUser && !$authUser->can('manage_all_stores')) {
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        } else {
            $stores = Store::orderBy('code')->get();
        }

        // ── Tự động chọn store mặc định cho người dùng ──
        if (!$storeId && $authUser) {
            if ($authUser->role === 'area_manager') {
                $storeId = $authUser->store ? Store::where('area_id', $authUser->store->area_id)->value('id') : null;
            } elseif (!$authUser->can('manage_all_stores')) {
                $storeId = $authUser->store_id;
            }
        }

        $users       = collect();
        $dailyTarget = null;
        $isLocked    = false;
        $kpiData     = [];
        $totals      = [];

        if ($storeId) {
            $usersQuery = User::with('position')
                ->where('store_id', $storeId)
                ->where('status', 1)
                ->orderBy('full_name');

            // Nhân viên thường + CHP: Chỉ nhìn thấy và nhập công của chính mình
            // QLCH và Admin mới được xem + nhập công toàn bộ nhân viên
            $isFullManager = $authUser->can('manage_all_stores')
                || ($authUser->can('manage_own_store') && in_array($authUser->getGroupRoleName(), ['QLCH']));

            if ($authUser && !$isFullManager) {
                $usersQuery->where('id', $authUser->id);
            }

            $users = $usersQuery->get();

            // Đảm bảo người đang đăng nhập luôn có trong danh sách
            if ($authUser && $users->where('id', $authUser->id)->isEmpty()) {
                $selfUser = User::with('position')->find($authUser->id);
                if ($selfUser) {
                    $users = collect([$selfUser])->merge($users)->sortBy('full_name')->values();
                }
            }

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

        // ── Kiểm tra quyền chỉnh sửa ──
        $authUser = auth()->user();
        $canEdit = false;
        if ($authUser->role === 'admin') {
            $canEdit = true;
        } elseif ($authUser->can('manage_own_store') && $authUser->store_id == $request->store_id) {
            $canEdit = true;
        } elseif ($authUser->id == $request->user_id) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Bạn không có quyền chỉnh sửa dòng công của nhân viên khác!'
            ], 403);
        }

        // ── Kiểm tra ngày đã khóa chưa và quyền bypass ──
        $dayIsLocked = \App\Models\ShiftRecord::where('store_id', $request->store_id)
            ->where('date', $request->date)
            ->where('is_locked', true)
            ->exists();

        if ($dayIsLocked && !$authUser->can('bypass_locked_day')) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Ngày này đã bị khóa. Bạn không có quyền chỉnh sửa dữ liệu đã khóa!'
            ], 403);
        }

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
        $authUser = auth()->user();
        if (!$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $request->store_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Bạn không có quyền xóa dòng công của cửa hàng này!'
            ], 403);
        }

        // ── Kiểm tra ngày đã khóa chưa và quyền bypass ──
        $dayIsLocked = \App\Models\ShiftRecord::where('store_id', $request->store_id)
            ->where('date', $request->date)
            ->where('is_locked', true)
            ->exists();

        if ($dayIsLocked && !$authUser->can('bypass_locked_day')) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Ngày này đã bị khóa. Bạn không có quyền xóa dữ liệu đã khóa!'
            ], 403);
        }

        $query = ShiftRecord::where('user_id', $userId)
            ->where('store_id', $request->store_id)
            ->where('date', $request->date);

        if (!$authUser->can('bypass_locked_day')) {
            $query->where('is_locked', false);
        }
        $query->delete();

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

        $authUser = auth()->user();
        if ($authUser && !$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $storeId)) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Bạn không có quyền thực hiện chức năng cân bằng KPI!'
            ], 403);
        }

        // ── Kiểm tra ngày đã khóa chưa và quyền bypass ──
        $dayIsLocked = \App\Models\ShiftRecord::where('store_id', $storeId)
            ->where('date', $date)
            ->where('is_locked', true)
            ->exists();

        if ($dayIsLocked && !$authUser->can('bypass_locked_day')) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Ngày này đã bị khóa. Bạn không có quyền cân bằng KPI cho ngày đã khóa!'
            ], 403);
        }

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
        $authUser = auth()->user();
        if ($authUser && !$authUser->can('lock_day')) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Bạn không có quyền khóa ngày.'
            ], 403);
        }

        $storeId = $request->store_id;
        $date    = $request->date;

        // Kiểm tra xem tháng của ngày này có bị khóa hoàn toàn chưa
        $month = date('Y-m', strtotime($date));
        $totalShifts = ShiftRecord::where('store_id', $storeId)->where('date', 'like', "$month%")->count();
        $lockedShifts = ShiftRecord::where('store_id', $storeId)->where('date', 'like', "$month%")->where('is_locked', true)->count();
        if ($totalShifts > 0 && $totalShifts === $lockedShifts) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Tháng này đã được khóa. Không thể thực hiện khóa ngày!'
            ], 403);
        }

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

        if (!$kpiConfig) {
            return;
        }

        // Lấy daily_ratios: {1: 40, 2: 40, ..., 5: 60, 6: 60, 7: 60}
        $dailyRatios = [];
        foreach (($kpiConfig->daily_ratios ?? []) as $k => $v) {
            $dailyRatios[(int)$k] = (float)$v;
        }

        // Chỉ lấy daily targets trong tuần hiện tại
        $weeklyTargets = DailyTarget::where('kpi_config_id', $kpiConfig->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $weeklyTotal = $weeklyTargets->sum('target_amount');

        $lockedSum = 0;
        $unlockedDays = [];

        foreach ($weeklyTargets as $t) {
            if ($t->date <= $date) {
                $act = (float)ShiftRecord::where('store_id', $storeId)
                    ->where('date', $t->date)
                    ->sum('personal_revenue');
                $t->rebalanced_target = $act;
                $t->save();
                $lockedSum += $act;
            } else {
                $unlockedDays[] = $t;
            }
        }

        // KPI còn lại cần đạt trong các ngày chưa khóa
        $remaining = max(0, $weeklyTotal - $lockedSum);

        if (count($unlockedDays) > 0) {
            // Tổng trọng số các ngày chưa khóa
            $totalW = collect($unlockedDays)->sum(function ($d) use ($dailyRatios) {
                $dow = Carbon::parse($d->date)->isoWeekday();
                return $dailyRatios[$dow] ?? 1.0;
            });

            foreach ($unlockedDays as $ud) {
                $dow = Carbon::parse($ud->date)->isoWeekday();
                $w   = $dailyRatios[$dow] ?? 1.0;
                $ud->rebalanced_target = $totalW > 0 ? round($remaining * $w / $totalW, 2) : 0;
                $ud->save();
            }
        }
    }
}
