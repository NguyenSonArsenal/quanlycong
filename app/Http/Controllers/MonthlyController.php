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
        $month     = $request->get('month', date('Y-m'));
        $storeId   = $request->get('store_id');
        $userId    = $request->get('user_id');
        $weekNum   = $request->get('week_num');   // 1–52
        $dateFrom  = $request->get('date_from');  // Y-m-d
        $dateTo    = $request->get('date_to');    // Y-m-d

        $authUser = auth()->user();
        if ($authUser->role === 'area_manager') {
            // Area Manager: giới hạn storeId chỉ nằm trong các store của area của mình
            if ($storeId) {
                $targetStore = Store::find($storeId);
                if (!$targetStore || $targetStore->area_id !== ($authUser->store ? $authUser->store->area_id : null)) {
                    $storeId = $authUser->store ? Store::where('area_id', $authUser->store->area_id)->value('id') : null;
                }
            } else {
                $storeId = $authUser->store ? Store::where('area_id', $authUser->store->area_id)->value('id') : null;
            }
        } elseif (!$authUser->can('manage_all_stores')) {
            // QLCH / CHP / Nhân viên: Chỉ được xem store của mình
            $storeId = $authUser->store_id;
            if (!$authUser->can('manage_own_store')) {
                // Nhân viên thường: Chỉ được xem chính mình
                $userId = $authUser->id;
            }
        }

        // Giới hạn dropdown stores theo phân quyền
        if ($authUser->role === 'admin') {
            $stores = Store::orderBy('code')->get();
        } elseif ($authUser->role === 'area_manager') {
            $areaId = $authUser->store ? $authUser->store->area_id : null;
            $stores = Store::where('area_id', $areaId)->orderBy('code')->get();
        } else {
            // QLCH / CHP / nhân viên: chỉ thấy store của mình
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        }

        $allUsers = collect();
        $rows     = [];
        $grandTotalDT    = 0;
        $grandTotalHours = 0;
        $storeTarget     = 0;
        $kpiPctStore     = 0;
        $selectedStore   = null;
        $selectedUser    = null;
        $storeSummaries  = [];

        if ($storeId) {
            $selectedStore = Store::find($storeId);

            $allUsersQuery = User::with('position')
                ->where('store_id', $storeId)
                ->where('status', 1)
                ->orderBy('full_name');

            if (!$authUser->can('manage_all_stores') && !$authUser->can('manage_own_store')) {
                $allUsersQuery->where('id', $authUser->id);
            }
            $allUsers = $allUsersQuery->get();

            // Đảm bảo người dùng đang đăng nhập luôn xuất hiện trong dropdown nhân viên
            if ($authUser && $allUsers->where('id', $authUser->id)->isEmpty()) {
                $selfUser = User::with('position')->find($authUser->id);
                if ($selfUser) {
                    $allUsers = collect([$selfUser])->merge($allUsers)->sortBy('full_name')->values();
                }
            }

            $userScope  = $userId ? [$userId] : $allUsers->pluck('id')->toArray();
            $usersKeyed = $allUsers->keyBy('id');

            if ($userId) {
                $selectedUser = $allUsers->firstWhere('id', $userId);
            }

            // Build date range từ filter
            $qDateFrom = $dateFrom ?: $month . '-01';
            $qDateTo   = $dateTo   ?: Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

            $allShifts = ShiftRecord::where('store_id', $storeId)
                ->whereBetween('date', [$qDateFrom, $qDateTo])
                ->whereIn('user_id', $userScope)
                ->get();

            $allKpi = EmployeeDailyKpi::where('store_id', $storeId)
                ->whereBetween('date', [$qDateFrom, $qDateTo])
                ->whereIn('user_id', $userScope)
                ->get();

            $shiftsByDateUser = $allShifts->groupBy(fn($s) => $s->date . '|' . $s->user_id);
            $kpiByDateUser    = $allKpi->groupBy(fn($k) => $k->date . '|' . $k->user_id);

            $keys = $shiftsByDateUser->keys()
                ->merge($kpiByDateUser->keys())
                ->unique()->sort();

            foreach ($keys as $key) {
                [$date, $uid] = explode('|', $key, 2);
                $user = $usersKeyed->get($uid);
                if (!$user) continue;

                $shifts  = $shiftsByDateUser->get($key, collect());
                $kpi     = $kpiByDateUser->get($key, collect())->first();
                $carbon  = Carbon::parse($date);

                // Filter theo tuần ISO
                if ($weekNum && $carbon->weekOfYear != $weekNum) continue;

                $gcSang  = (float)($shifts->firstWhere('shift_type', 'morning')?->hours ?? 0);
                $gcChieu = (float)($shifts->firstWhere('shift_type', 'afternoon')?->hours ?? 0);
                $gcToi   = (float)($shifts->firstWhere('shift_type', 'evening')?->hours ?? 0);
                $gcBs    = (float)($shifts->filter(fn($s) => !in_array($s->shift_type, ['morning','afternoon','evening']))->sum('hours'));
                $dtSang  = (float)($shifts->firstWhere('shift_type', 'morning')?->personal_revenue ?? 0);
                $dtChieu = (float)($shifts->firstWhere('shift_type', 'afternoon')?->personal_revenue ?? 0);
                $dtToi   = (float)($shifts->firstWhere('shift_type', 'evening')?->personal_revenue ?? 0);
                $totalH  = $gcSang + $gcChieu + $gcToi + $gcBs;
                $totalDT = $dtSang + $dtChieu + $dtToi;

                $rows[] = [
                    'date'        => $date,
                    'date_fmt'    => $carbon->format('d/m/Y'),
                    'day_of_week' => $this->getDayOfWeekVi($carbon->isoWeekday()),
                    'week_num'    => $carbon->weekOfYear,
                    'week_label'  => 'Tuần ' . $carbon->weekOfYear,
                    'month_fmt'   => $carbon->format('m/Y'),
                    'user'        => $user,
                    'gc_sang'     => $gcSang,  'gc_chieu' => $gcChieu,
                    'gc_toi'      => $gcToi,   'gc_bs'    => $gcBs,
                    'dt_sang'     => $dtSang,  'dt_chieu' => $dtChieu,  'dt_toi' => $dtToi,
                    'so_kh'       => $kpi ? (int)$kpi->customers : 0,
                    'thu_do'      => $kpi ? (int)$kpi->fitting_rooms : 0,
                    'so_don'      => $kpi ? (int)$kpi->orders : 0,
                    'so_sp'       => $kpi ? (int)$kpi->products : 0,
                    'kpi_pct'     => $kpi ? (float)$kpi->kpi_percentage : 0,
                    'target'      => $kpi ? (float)$kpi->target_amount : 0,
                    'total_hours' => $totalH,  'total_dt' => $totalDT,
                ];
            }

            usort($rows, function ($a, $b) {
                $c = strcmp($a['date'], $b['date']);
                return $c !== 0 ? $c : strcmp($a['user']->full_name, $b['user']->full_name);
            });

            $grandTotalDT    = array_sum(array_column($rows, 'total_dt'));
            $grandTotalHours = array_sum(array_column($rows, 'total_hours'));

            $kpiConfig   = KpiConfig::where('store_id', $storeId)->where('month', $month)->first();
            $storeTarget = $kpiConfig ? (float)$kpiConfig->total_target : 0;
            $kpiPctStore = $storeTarget > 0 ? round($grandTotalDT / $storeTarget * 100, 1) : 0;
        } else {
            // Lấy danh sách thống kê toàn bộ cửa hàng cho trang tổng quan chính
            foreach ($stores as $st) {
                // Target KPI
                $kpiConfig = KpiConfig::where('store_id', $st->id)->where('month', $month)->first();
                $target = $kpiConfig ? (float)$kpiConfig->total_target : 0;

                // DT thực tế nhân viên nhập (sum personal_revenue)
                $reportedRevenue = (float)ShiftRecord::where('store_id', $st->id)
                    ->where('date', 'like', "$month%")
                    ->sum('personal_revenue');

                // DT thực tế API (giả lập)
                // Ta mock một sai số cố định theo mã cửa hàng để tạo ra chênh lệch ngẫu nhiên nhưng nhất quán
                $offset = 0.015; // mặc định lệch 1.5%
                if (str_contains($st->code, 'K01') || str_contains($st->code, '01')) {
                    $offset = -0.055; // lệch -5.5% (triggers alert)
                } elseif (str_contains($st->code, 'K03') || str_contains($st->code, '03')) {
                    $offset = -0.065; // lệch -6.5% (triggers alert)
                } elseif (str_contains($st->code, 'K02') || str_contains($st->code, '02')) {
                    $offset = 0.02;  // lệch +2%
                }
                $apiRevenue = $reportedRevenue * (1 + $offset);

                // Tính % hoàn thành KPI
                $kpiPct = $target > 0 ? round($reportedRevenue / $target * 100, 1) : 0;

                // Tính % chênh lệch giữa nhập tay vs API
                $diffPct = $reportedRevenue > 0 ? round(abs($reportedRevenue - $apiRevenue) / $reportedRevenue * 100, 1) : 0;
                $isDiscrepancy = $diffPct > 5.0;

                // Trạng thái khoá tháng
                $totalShifts = ShiftRecord::where('store_id', $st->id)->where('date', 'like', "$month%")->count();
                $lockedShifts = ShiftRecord::where('store_id', $st->id)->where('date', 'like', "$month%")->where('is_locked', true)->count();
                $isMonthLocked = $totalShifts > 0 && $totalShifts === $lockedShifts;

                $storeSummaries[] = [
                    'store' => $st,
                    'target' => $target,
                    'reported_revenue' => $reportedRevenue,
                    'api_revenue' => $apiRevenue,
                    'kpi_pct' => $kpiPct,
                    'diff_pct' => $diffPct,
                    'is_discrepancy' => $isDiscrepancy,
                    'is_month_locked' => $isMonthLocked,
                    'total_shifts' => $totalShifts,
                ];
            }
        }

        return view('monthly.index', compact(
            'month', 'stores', 'storeId', 'userId',
            'weekNum', 'dateFrom', 'dateTo',
            'allUsers', 'selectedStore', 'selectedUser',
            'rows', 'grandTotalDT', 'grandTotalHours',
            'storeTarget', 'kpiPctStore', 'storeSummaries'
        ));
    }



    public function show(Request $request, \App\Models\Store $store)
    {
        $authUser = auth()->user();
        $isAreaStore = false;
        if ($authUser->role === 'area_manager' && $authUser->store && $store->area_id === $authUser->store->area_id) {
            $isAreaStore = true;
        }
        if (!$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $store->id) && !$isAreaStore) {
            abort(403, '❌ Bạn không có quyền truy cập thông tin cửa hàng này.');
        }

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

    // ── Bảng doanh thu theo ngày (Cal_Bảng doanh thu) ──
    public function revenue(Request $request, \App\Models\Store $store)
    {
        $authUser = auth()->user();
        $isAreaStore = false;
        if ($authUser->role === 'area_manager' && $authUser->store && $store->area_id === $authUser->store->area_id) {
            $isAreaStore = true;
        }
        if (!$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $store->id) && !$isAreaStore) {
            abort(403, '❌ Bạn không có quyền xem bảng doanh thu cửa hàng này.');
        }

        $month = $request->get('month', date('Y-m'));

        // Lấy toàn bộ shift records của store trong tháng
        $allShifts = ShiftRecord::where('store_id', $store->id)
            ->where('date', 'like', "$month%")
            ->get();

        // Lấy toàn bộ kpi records (SLKH, SLHD, SLSP) của store trong tháng
        $allKpi = EmployeeDailyKpi::where('store_id', $store->id)
            ->where('date', 'like', "$month%")
            ->get();

        // Group theo ngày
        $dateGroups = $allShifts->groupBy('date');
        $kpiGroups  = $allKpi->groupBy('date');

        $dailyRows = [];
        foreach ($dateGroups as $date => $shifts) {
            $carbon = Carbon::parse($date);
            $kpiDay = $kpiGroups->get($date, collect());

            $morning   = (float)$shifts->where('shift_type', 'morning')->sum('personal_revenue');
            $afternoon = (float)$shifts->where('shift_type', 'afternoon')->sum('personal_revenue');
            $evening   = (float)$shifts->where('shift_type', 'evening')->sum('personal_revenue');
            $totalDT   = $morning + $afternoon + $evening;
            $totalHours = (float)$shifts->sum('hours');

            $dailyRows[] = [
                'date'       => $date,
                'date_fmt'   => $carbon->format('d/m/Y'),
                'day_of_week'=> $this->getDayOfWeekVi($carbon->isoWeekday()),
                'week_num'   => $carbon->weekOfYear,
                'week_label' => 'Tuần ' . $carbon->weekOfYear,
                'month_fmt'  => $carbon->format('m/Y'),
                'slkh'       => (int)$kpiDay->sum('customers'),
                'slhd'       => (int)$kpiDay->sum('orders'),
                'slsp'       => (int)$kpiDay->sum('products'),
                'morning'    => $morning,
                'afternoon'  => $afternoon,
                'evening'    => $evening,
                'total_dt'   => $totalDT,
                'total_hours'=> $totalHours,
            ];
        }

        // Sort theo ngày tăng dần
        usort($dailyRows, fn($a, $b) => strcmp($a['date'], $b['date']));

        // Tổng tháng
        $grandTotalDT    = array_sum(array_column($dailyRows, 'total_dt'));
        $grandTotalHours = array_sum(array_column($dailyRows, 'total_hours'));
        $grandSlkh       = array_sum(array_column($dailyRows, 'slkh'));
        $grandSlhd       = array_sum(array_column($dailyRows, 'slhd'));
        $grandSlsp       = array_sum(array_column($dailyRows, 'slsp'));
        $maxDayDT        = count($dailyRows) > 0 ? max(array_column($dailyRows, 'total_dt')) : 0;

        // KPI tháng
        $kpiConfig   = KpiConfig::where('store_id', $store->id)->where('month', $month)->first();
        $storeTarget = $kpiConfig ? (float)$kpiConfig->total_target : 0;
        $kpiPct      = $storeTarget > 0 ? round($grandTotalDT / $storeTarget * 100, 1) : 0;

        return view('monthly.revenue', compact(
            'store', 'month', 'dailyRows',
            'grandTotalDT', 'grandTotalHours', 'grandSlkh', 'grandSlhd', 'grandSlsp',
            'maxDayDT', 'storeTarget', 'kpiPct'
        ));
    }

    // ── Cal_Bảng công: toàn bộ NV × tất cả ngày (Bảng lọc khi có user_id) ──
    public function calendar(Request $request, \App\Models\Store $store)
    {
        $month  = $request->get('month', date('Y-m'));
        $userId = $request->get('user_id');   // null = toàn bộ NV

        $authUser = auth()->user();
        $isAreaStore = false;
        if ($authUser->role === 'area_manager' && $authUser->store && $store->area_id === $authUser->store->area_id) {
            $isAreaStore = true;
        }
        if (!$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $store->id) && !$isAreaStore) {
            if ($authUser->store_id != $store->id) {
                abort(403, '❌ Bạn không có quyền xem bảng công cửa hàng này.');
            }
            $userId = $authUser->id; // Hạn chế nhân viên thường chỉ xem được của chính mình
        }

        // ── Load users ──
        $usersQuery = User::with('position')
            ->where('store_id', $store->id)
            ->where('status', 1)
            ->orderBy('full_name');

        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        $users   = $usersQuery->get()->keyBy('id');
        $allUsersQuery = User::with('position')
            ->where('store_id', $store->id)
            ->where('status', 1)
            ->orderBy('full_name');

        if (!$authUser->can('manage_all_stores') && !($authUser->can('manage_own_store') && $authUser->store_id == $store->id) && !$isAreaStore) {
            $allUsersQuery->where('id', $authUser->id);
        }
        $allUsers = $allUsersQuery->get();

        // ── Load shift records ──
        $shiftsQuery = ShiftRecord::where('store_id', $store->id)
            ->where('date', 'like', "$month%");
        if ($userId) {
            $shiftsQuery->where('user_id', $userId);
        }
        $allShifts = $shiftsQuery->get();

        // ── Load employee_daily_kpi ──
        $kpiQuery = EmployeeDailyKpi::where('store_id', $store->id)
            ->where('date', 'like', "$month%");
        if ($userId) {
            $kpiQuery->where('user_id', $userId);
        }
        $allKpi = $kpiQuery->get();

        // ── Build rows: mỗi row = 1 user × 1 ngày ──
        // Group shifts by (date, user_id)
        $shiftsByDateUser = $allShifts->groupBy(fn($s) => $s->date . '|' . $s->user_id);
        $kpiByDateUser    = $allKpi->groupBy(fn($k) => $k->date . '|' . $k->user_id);

        // Lấy tập hợp key date|user_id unique
        $keys = $shiftsByDateUser->keys()
            ->merge($kpiByDateUser->keys())
            ->unique()
            ->sort(); // sort theo date|user_id

        $rows = [];
        foreach ($keys as $key) {
            [$date, $uid] = explode('|', $key, 2);
            $user   = $users->get($uid);
            if (!$user) continue;   // user không thuộc store/filter

            $shifts = $shiftsByDateUser->get($key, collect());
            $kpi    = $kpiByDateUser->get($key, collect())->first();
            $carbon = Carbon::parse($date);

            $gcSang   = (float)($shifts->firstWhere('shift_type', 'morning')?->hours ?? 0);
            $gcChieu  = (float)($shifts->firstWhere('shift_type', 'afternoon')?->hours ?? 0);
            $gcToi    = (float)($shifts->firstWhere('shift_type', 'evening')?->hours ?? 0);
            $gcBs     = (float)($shifts->filter(fn($s) => !in_array($s->shift_type, ['morning','afternoon','evening']))->sum('hours'));

            $dtSang   = (float)($shifts->firstWhere('shift_type', 'morning')?->personal_revenue ?? 0);
            $dtChieu  = (float)($shifts->firstWhere('shift_type', 'afternoon')?->personal_revenue ?? 0);
            $dtToi    = (float)($shifts->firstWhere('shift_type', 'evening')?->personal_revenue ?? 0);

            $totalH   = $gcSang + $gcChieu + $gcToi + $gcBs;
            $totalDT  = $dtSang + $dtChieu + $dtToi;
            $kpiPct   = $kpi ? (float)$kpi->kpi_percentage : 0;
            $target   = $kpi ? (float)$kpi->target_amount : 0;

            $rows[] = [
                'date'        => $date,
                'date_fmt'    => $carbon->format('d/m/Y'),
                'day_of_week' => $this->getDayOfWeekVi($carbon->isoWeekday()),
                'week_label'  => 'Tuần ' . $carbon->weekOfYear,
                'month_fmt'   => $carbon->format('m/Y'),
                'user'        => $user,
                'gc_sang'     => $gcSang,
                'gc_chieu'    => $gcChieu,
                'gc_toi'      => $gcToi,
                'gc_bs'       => $gcBs,
                'dt_sang'     => $dtSang,
                'dt_chieu'    => $dtChieu,
                'dt_toi'      => $dtToi,
                'so_kh'       => $kpi ? (int)$kpi->customers : 0,
                'thu_do'      => $kpi ? (int)$kpi->fitting_rooms : 0,
                'so_don'      => $kpi ? (int)$kpi->orders : 0,
                'so_sp'       => $kpi ? (int)$kpi->products : 0,
                'kpi_pct'     => $kpiPct,
                'target'      => $target,
                'total_hours' => $totalH,
                'total_dt'    => $totalDT,
            ];
        }

        // Sort: ngày tăng dần, cùng ngày thì sort theo tên NV
        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['date'], $b['date']);
            return $cmp !== 0 ? $cmp : strcmp($a['user']->full_name, $b['user']->full_name);
        });

        // ── Tổng hợp ──
        $grandTotalDT    = array_sum(array_column($rows, 'total_dt'));
        $grandTotalHours = array_sum(array_column($rows, 'total_hours'));

        // User được chọn (khi filter)
        $selectedUser = $userId ? $allUsers->firstWhere('id', $userId) : null;

        // KPI tháng store
        $kpiConfig   = KpiConfig::where('store_id', $store->id)->where('month', $month)->first();
        $storeTarget = $kpiConfig ? (float)$kpiConfig->total_target : 0;
        $kpiPctStore = $storeTarget > 0 ? round($grandTotalDT / $storeTarget * 100, 1) : 0;

        return view('monthly.calendar', compact(
            'store', 'month', 'rows', 'allUsers',
            'userId', 'selectedUser',
            'grandTotalDT', 'grandTotalHours',
            'storeTarget', 'kpiPctStore'
        ));
    }

    private function getDayOfWeekVi(int $iso): string
    {
        return match($iso) {
            1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5',
            5 => 'T6', 6 => 'T7', 7 => 'CN',
            default => '?'
        };
    }

    public function toggleLockMonth(Request $request, $storeId)
    {
        // Chỉ admin mới có quyền khoá/mở khoá tháng
        if (!auth()->user()->can('lock_month') && !auth()->user()->can('unlock_month')) {
            return redirect()->back()->with('error', '❌ Bạn không có quyền khoá hoặc mở khoá tháng.');
        }

        $month = $request->input('month', date('Y-m'));
        $action = $request->input('action'); // 'lock' hoặc 'unlock'

        $isLock = $action === 'lock';

        ShiftRecord::where('store_id', $storeId)
            ->where('date', 'like', "$month%")
            ->update(['is_locked' => $isLock]);

        $msg = $isLock ? "🔒 Đã khoá bảng công toàn bộ tháng {$month}!" : "🔓 Đã mở khoá bảng công toàn bộ tháng {$month}!";
        return redirect()->back()->with('success', $msg);
    }
}
