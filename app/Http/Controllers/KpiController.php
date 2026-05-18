<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\KpiConfig;
use App\Models\DailyTarget;
use App\Models\ShiftRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('config_kpi')) {
                abort(403, '❌ Bạn không có quyền cấu hình KPI.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $stores = Store::orderBy('code')->get();

        // Lấy danh sách năm có KPI để fill filter
        $years = KpiConfig::selectRaw('YEAR(month) as y')
            ->distinct()->orderByDesc('y')->pluck('y');

        $query = KpiConfig::with(['store', 'dailyTargets'])->orderBy('month', 'desc');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('year')) {
            $query->whereYear('month', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereRaw('MONTH(month) = ?', [(int)$request->month]);
        }

        $configs = $query->paginate(12)->withQueryString();

        return view('kpis.index', compact('stores', 'configs', 'years'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id'     => 'required|exists:stores,id',
            'month'        => 'required|date_format:Y-m',
            'total_target' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Tỷ trọng ngày mặc định: T2-T5 (early) vs T6-CN (late)
            // Sử dụng ratio từ form để tính tỷ trọng 7 ngày trong tuần
            // daily_ratios lưu % từ từng loại ngày (tỷ lệ đúng là đủ)
            $earlyPct = max(0.01, (float)($request->input('day_weights.1', config('config.default_weights.weekday', 45))));
            $latePct  = max(0.01, (float)($request->input('day_weights.5', config('config.default_weights.weekend', 55))));
            $dailyRatios = [
                1 => $earlyPct, 2 => $earlyPct, 3 => $earlyPct, 4 => $earlyPct,
                5 => $latePct,  6 => $latePct,  7 => $latePct,
            ];

            // weekly_ratios mặc định chia đều
            // (Admin sẽ vào trang chi tiết để tinh chỉnh)
            $weeklyRatios = [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20];

            // Tỷ trọng ca mặc định
            $shiftWeekday = ['morning' => 10, 'afternoon' => 36, 'evening' => 54];
            $shiftWeekend = ['morning' => 12, 'afternoon' => 45, 'evening' => 43];

            $config = KpiConfig::updateOrCreate(
                ['store_id' => $request->store_id, 'month' => $request->month],
                [
                    'total_target'          => $request->total_target,
                    'weekly_ratios'         => $weeklyRatios,
                    'daily_ratios'          => $dailyRatios,
                    'shift_ratios_weekday'  => $shiftWeekday,
                    'shift_ratios_weekend'  => $shiftWeekend,
                ]
            );

            $this->generateDailyTargets($config);

            DB::commit();
            return redirect()->route('fe.kpi-config.show', $config->id)
                ->with('success', 'Đã tạo KPI thành công! Cấu hình chi tiết tỷ trọng bên dưới và nhấn Lưu.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // Cập nhật basic info (từ Edit modal trên màn list)
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id'     => 'required|exists:stores,id',
            'month'        => 'required|date_format:Y-m',
            'total_target' => 'required|numeric|min:1',
        ]);

        $config = KpiConfig::findOrFail($id);
        DB::beginTransaction();
        try {
            $config->update([
                'store_id'     => $request->store_id,
                'month'        => $request->month,
                'total_target' => $request->total_target,
            ]);
            // Tính lại daily targets theo tổng mới
            $this->generateDailyTargets($config->fresh());
            DB::commit();
            return redirect()->route('fe.kpi-config.index')
                ->with('success', 'Đã cập nhật KPI ' . $config->store->code . ' / ' . $config->month . ' thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // Xoá config + toàn bộ daily targets
    public function destroy($id)
    {
        $config = KpiConfig::findOrFail($id);
        $label  = $config->store->code . ' / ' . $config->month;
        DB::beginTransaction();
        try {
            DailyTarget::where('kpi_config_id', $id)->delete();
            $config->delete();
            DB::commit();
            return redirect()->route('fe.kpi-config.index')
                ->with('success', 'Đã xoá cấu hình KPI: ' . $label);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $config = KpiConfig::with(['store', 'dailyTargets' => function($q) {
            $q->orderBy('date');
        }])->findOrFail($id);

        // ── Tính ISO week map từ DATE (không tin week_number trong DB) ──
        // Tuần mới bắt đầu T2, kết thúc CN. Tuần 1 từ ngày 1 đến CN đầu tiên.
        $weekByDate = [];
        $wn         = 1;
        $cur        = Carbon::parse($config->month . '-01');
        $monthEnd   = $cur->copy()->endOfMonth();
        while ($cur->lte($monthEnd)) {
            $weekByDate[$cur->toDateString()] = $wn;
            if ($cur->isoWeekday() === 7 && $wn < 5) { $wn++; }
            $cur->addDay();
        }

        // ── Nhóm daily_targets theo tuần (dùng date, không dùng week_number DB) ──
        $weeks = [];
        foreach ($config->dailyTargets as $target) {
            $wn = $weekByDate[$target->date] ?? 1;
            if (!isset($weeks[$wn])) {
                $weeks[$wn] = ['weight' => 0.0, 'targets' => []];
            }
            $weeks[$wn]['targets'][] = $target;
        }
        ksort($weeks);

        // ── Cast JSON keys sang integer ──
        $dailyRatios = [];
        foreach (($config->daily_ratios ?? []) as $k => $v) {
            $dailyRatios[(int)$k] = (float)$v;
        }
        $weeklyRatios = [];
        foreach (($config->weekly_ratios ?? []) as $k => $v) {
            $weeklyRatios[(int)$k] = (float)$v;
        }
        // Gán weight từ config (không dùng DB week_weight)
        for ($i = 1; $i <= 5; $i++) {
            if (isset($weeks[$i])) {
                $weeks[$i]['weight'] = $weeklyRatios[$i] ?? 20.0;
            }
        }

        // ── Doanh thu thực tế theo ngày ──
        $actualByDate = ShiftRecord::where('store_id', $config->store_id)
            ->where('date', 'like', $config->month . '%')
            ->select('date', DB::raw('SUM(personal_revenue) as actual'))
            ->groupBy('date')
            ->pluck('actual', 'date');

        // ── Các ngày đã khóa trong tháng ──
        $lockedDates = ShiftRecord::where('store_id', $config->store_id)
            ->where('date', 'like', $config->month . '%')
            ->where('is_locked', true)
            ->distinct()
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))
            ->flip()
            ->toArray(); // ['11/05' => 0, '12/05' => 1, ...]

        // ── Dữ liệu cho switcher ──
        $stores  = Store::orderBy('code')->get();
        $configs = KpiConfig::with('store')->orderBy('month', 'desc')->get();

        return view('kpis.show', compact(
            'config', 'weeks', 'actualByDate',
            'dailyRatios', 'weeklyRatios',
            'stores', 'configs', 'lockedDates'
        ));
    }

    // ── Khóa tuần → rebalance KPI các tuần còn lại trong tháng ──
    // ── Khóa tuần → rebalance KPI các tuần còn lại trong tháng ──
    public function lockWeek(Request $request, $id)
    {
        $config = KpiConfig::findOrFail($id);
        if ($this->isMonthLocked($config)) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Tháng này đã được khóa. Không thể khóa tuần!'
            ], 403);
        }
        $weekNo = (int)$request->week_number; // 1-5

        $lockedWeeks = $config->locked_weeks ?? [];
        if (!in_array($weekNo, $lockedWeeks)) {
            $lockedWeeks[] = $weekNo;
        }

        $config->locked_weeks = $lockedWeeks;
        $config->save();

        // Tái phân bổ
        $this->rebalanceAllWeeks($config);

        // Lấy lại dữ liệu sau khi rebalance để trả về UI
        $allTargets = DailyTarget::where('kpi_config_id', $id)->orderBy('date')->get();

        $weekByDate = [];
        $wn = 1;
        $cur = Carbon::parse($config->month . '-01');
        $monthEnd = $cur->copy()->endOfMonth();
        while ($cur->lte($monthEnd)) {
            $weekByDate[$cur->toDateString()] = $wn;
            if ($cur->isoWeekday() === 7 && $wn < 5) { $wn++; }
            $cur->addDay();
        }

        $currWDates = $allTargets->filter(fn($t) => ($weekByDate[$t->date] ?? 0) === $weekNo)->pluck('date')->toArray();
        $actualThisWeek = \App\Models\ShiftRecord::where('store_id', $config->store_id)
            ->whereIn('date', $currWDates)
            ->sum('personal_revenue');
        $targetThisWeek = $allTargets->filter(fn($t) => ($weekByDate[$t->date] ?? 0) === $weekNo)
            ->sum(fn($t) => !is_null($t->rebalanced_target) ? $t->rebalanced_target : $t->target_amount);
        $diff = $actualThisWeek - $targetThisWeek;

        $futureWeeks = collect(range($weekNo + 1, 5))->filter(fn($w) => isset($config->weekly_ratios[$w]));

        return response()->json([
            'status'          => 'success',
            'week'            => $weekNo,
            'actual'          => $actualThisWeek,
            'target'          => $targetThisWeek,
            'diff'            => $diff,
            'future_weeks'    => $futureWeeks->values(),
        ]);
    }

    // ── Mở khóa tuần → rebalance KPI các tuần còn lại ──
    public function unlockWeek(Request $request, $id)
    {
        $config = KpiConfig::findOrFail($id);
        if ($this->isMonthLocked($config)) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ Tháng này đã được khóa. Không thể mở khóa tuần!'
            ], 403);
        }
        $weekNo = (int)$request->week_number; // 1-5

        $lockedWeeks = $config->locked_weeks ?? [];
        $lockedWeeks = array_values(array_diff($lockedWeeks, [$weekNo]));

        $config->locked_weeks = $lockedWeeks;
        $config->save();

        // Tái phân bổ lại tất cả các tuần từ đầu dựa trên cấu hình locked_weeks mới
        $this->rebalanceAllWeeks($config);

        return response()->json([
            'status' => 'success',
            'week'   => $weekNo,
        ]);
    }

    // Helper dùng chung để tái phân bổ KPI cho các tuần tương lai
    private function rebalanceAllWeeks(KpiConfig $config)
    {
        $id = $config->id;

        // Lấy daily_ratios và weekly_ratios
        $dailyRatios = [];
        foreach (($config->daily_ratios ?? []) as $k => $v) {
            $dailyRatios[(int)$k] = (float)$v;
        }
        $weeklyRatios = [];
        foreach (($config->weekly_ratios ?? []) as $k => $v) {
            $weeklyRatios[(int)$k] = (float)$v;
        }

        $monthlyTotal = (float)$config->total_target;
        $allTargets = DailyTarget::where('kpi_config_id', $id)->orderBy('date')->get();

        // Map ngày → tuần
        $weekByDate = [];
        $wn = 1;
        $cur = Carbon::parse($config->month . '-01');
        $monthEnd = $cur->copy()->endOfMonth();
        while ($cur->lte($monthEnd)) {
            $weekByDate[$cur->toDateString()] = $wn;
            if ($cur->isoWeekday() === 7 && $wn < 5) { $wn++; }
            $cur->addDay();
        }

        $lockedWeeks = $config->locked_weeks ?? [];
        $maxLockedWeek = count($lockedWeeks) > 0 ? max($lockedWeeks) : 0;

        // Các ngày đã khóa công
        $lockedDates = \App\Models\ShiftRecord::where('store_id', $config->store_id)
            ->where('date', 'like', $config->month . '%')
            ->where('is_locked', true)
            ->distinct()
            ->pluck('date')
            ->toArray();

        // 1. Tính contribution của các tuần <= $maxLockedWeek
        $pastContribution = 0;
        for ($w = 1; $w <= 5; $w++) {
            $wDates = $allTargets->filter(fn($t) => ($weekByDate[$t->date] ?? 0) === $w)->pluck('date')->toArray();
            if (in_array($w, $lockedWeeks)) {
                // Đã khóa -> dùng doanh thu thực tế
                $actualW = \App\Models\ShiftRecord::where('store_id', $config->store_id)
                    ->whereIn('date', $wDates)
                    ->sum('personal_revenue');
                $pastContribution += $actualW;
            } elseif ($w <= $maxLockedWeek) {
                // Đã qua nhưng không khóa -> dùng target hiện tại của nó
                $targetW = $allTargets->filter(fn($t) => ($weekByDate[$t->date] ?? 0) === $w)
                    ->sum(fn($t) => !is_null($t->rebalanced_target) ? $t->rebalanced_target : $t->target_amount);
                $pastContribution += $targetW;
            }
        }

        // 2. KPI còn lại cho các tuần tương lai (> $maxLockedWeek)
        $futureKPI = $monthlyTotal - $pastContribution;

        // 3. Phân bổ cho các tuần tương lai
        $futureWeeks = collect(range($maxLockedWeek + 1, 5))->filter(fn($w) => isset($weeklyRatios[$w]));
        $totalFutureWeight = $futureWeeks->sum(fn($w) => $weeklyRatios[$w] ?? 0);

        foreach (range(1, 5) as $w) {
            $wDates = $allTargets->filter(fn($t) => ($weekByDate[$t->date] ?? 0) === $w);
            if ($w <= $maxLockedWeek) {
                // Tuần đã khóa: KHÔNG ghi đè rebalanced_target
                // Giữ nguyên target_amount gốc, chỉ fill nếu còn null
                foreach ($wDates as $ft) {
                    if (is_null($ft->rebalanced_target)) {
                        $ft->rebalanced_target = $ft->target_amount;
                        $ft->save();
                    }
                }
            } else {
                // Tuần tương lai: phân bổ lại theo tỉ lệ mới
                $wRatio = $weeklyRatios[$w] ?? 0;
                $newWeekTarget = $totalFutureWeight > 0 ? $futureKPI * $wRatio / $totalFutureWeight : 0;

                $weekDayTotal = $wDates->sum(function($d) use ($dailyRatios) {
                    return $dailyRatios[Carbon::parse($d->date)->isoWeekday()] ?? 1.0;
                });

                foreach ($wDates as $ft) {
                    if (in_array($ft->date, $lockedDates)) {
                        continue;
                    }
                    $dow = Carbon::parse($ft->date)->isoWeekday();
                    $dayRatio = $dailyRatios[$dow] ?? 1.0;
                    $ft->rebalanced_target = $weekDayTotal > 0 ? round($newWeekTarget * $dayRatio / $weekDayTotal, 2) : 0;
                    $ft->save();
                }
            }
        }
    }

    // Tính lại toàn bộ daily_targets từ config hiện tại
    public function regenerate($id)
    {
        $config = KpiConfig::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->generateDailyTargets($config);
            DB::commit();
            return redirect()->route('fe.kpi-config.show', $id)
                ->with('success', 'Đã tính lại Daily Targets thành công! Kiểm tra bảng bên dưới.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // Cập nhật Ma trận KPI (% Tuần + % Ngày trong tuần + Tỷ trọng ca + Tổng KPI)
    public function updateMatrix(Request $request, $id)
    {
        $config = KpiConfig::findOrFail($id);

        // Validate tổng % tuần = 100
        $weekWeights = collect($request->week_weights ?? []);
        if (abs($weekWeights->sum() - 100) > 0.5) {
            return redirect()->back()->with('error', 'Tổng tỷ trọng 5 tuần phải bằng 100%! Hiện: ' . round($weekWeights->sum(),2) . '%');
        }

        DB::beginTransaction();
        try {
            // Tổng KPI tháng (nếu thay đổi)
            $totalTarget = $request->filled('total_target') ? (float)$request->total_target : $config->total_target;

            // Tỷ trọng tuần
            $weeklyRatios = [];
            foreach ($request->week_weights as $wn => $ww) {
                $weeklyRatios[(int)$wn] = (float)$ww;
            }

            // Tỷ trọng ngày
            $dailyRatios = [];
            foreach ($request->day_weights as $dow => $dw) {
                $dailyRatios[(int)$dow] = (float)$dw;
            }

            // Tỷ trọng ca
            $shiftWeekday = $request->shift_weekday ?? ['morning' => 10, 'afternoon' => 36, 'evening' => 54];
            $shiftWeekend = $request->shift_weekend ?? ['morning' => 12, 'afternoon' => 45, 'evening' => 43];

            $config->update([
                'total_target'         => $totalTarget,
                'weekly_ratios'        => $weeklyRatios,
                'daily_ratios'         => $dailyRatios,
                'shift_ratios_weekday' => $shiftWeekday,
                'shift_ratios_weekend' => $shiftWeekend,
                'is_saved'             => true,
            ]);

            // Tính lại daily_targets
            $this->generateDailyTargets($config->fresh());

            DB::commit();
            return redirect()->back()->with('success', 'Đã cập nhật Ma trận KPI thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Sinh daily_targets từ config.
     * Tuần chia theo ISO: Tuần mới bắt đầu từ T2, kết thúc CN.
     * Tuần 1 = từ ngày 1 đến CN đầu tiên (có thể chỉ 1-3 ngày nếu ngày 1 là T6).
     * Ví dụ tháng 5/2026: T1 = 01-03/05 (T6,T7,CN), T2 = 04-10/05, ...
     */
    private function generateDailyTargets(KpiConfig $config)
    {
        $start = Carbon::parse($config->month . '-01')->startOfDay();
        $end   = $start->copy()->endOfMonth();

        // Cast keys sang integer để truy cập an toàn
        $weeklyRatios = [];
        foreach (($config->weekly_ratios ?? []) as $k => $v) {
            $weeklyRatios[(int)$k] = (float)$v;
        }
        if (empty($weeklyRatios)) {
            $weeklyRatios = [1=>20.0, 2=>20.0, 3=>20.0, 4=>20.0, 5=>20.0];
        }

        $dailyRatios = [];
        foreach (($config->daily_ratios ?? []) as $k => $v) {
            $dailyRatios[(int)$k] = (float)$v;
        }
        if (empty($dailyRatios)) {
            $dailyRatios = [1=>14.29, 2=>14.29, 3=>14.29, 4=>14.29, 5=>14.28, 6=>14.28, 7=>14.27];
        }

        // Nhóm ngày theo ISO week: tuần kết thúc CN, tuần mới bắt đầu T2
        // Tuần 1 bắt đầu từ ngày 1 (dù là thứ mấy), kết thúc CN đầu tiên
        $weekGroups = [];
        $weekNum    = 1;
        $current    = $start->copy();

        while ($current->lte($end)) {
            $weekGroups[$weekNum][] = $current->copy();
            // Hết CN → sang tuần mới (tối đa tuần 5)
            if ($current->isoWeekday() === 7 && $weekNum < 5) {
                $weekNum++;
            }
            $current->addDay();
        }
        ksort($weekGroups);

        DailyTarget::where('kpi_config_id', $config->id)->delete();

        foreach ($weekGroups as $wn => $dates) {
            $weekWeight = $weeklyRatios[$wn] ?? 20.0;
            $weekTarget = ($config->total_target * $weekWeight) / 100;

            // T\u1ed5ng day_weight ch\u1ec9 c\u1ee7a c\u00e1c ng\u00e0y th\u1ef1c t\u1ebf c\u00f3 trong tu\u1ea7n n\u00e0y
            $totalDayWeightInWeek = collect($dates)->sum(function($d) use ($dailyRatios) {
                $dow = $d->isoWeekday();
                return $dailyRatios[$dow] ?? 0;
            });

            foreach ($dates as $date) {
                $dow       = $date->isoWeekday();
                $dayWeight = $dailyRatios[$dow] ?? 0;
                $dayTarget = ($totalDayWeightInWeek > 0)
                    ? ($weekTarget * $dayWeight / $totalDayWeightInWeek)
                    : 0;

                DailyTarget::create([
                    'kpi_config_id'     => $config->id,
                    'date'              => $date->toDateString(),
                    'week_number'       => $wn,          // fix: dùng $wn (key) thay vì $weekNum
                    'target_amount'     => round($dayTarget, 2),
                    'rebalanced_target' => round($dayTarget, 2),
                ]);
            }
        }
    }

    private function isMonthLocked(KpiConfig $config): bool
    {
        $lockedWeeks = $config->locked_weeks ?? [];
        return count($lockedWeeks) >= 5;
    }
}
