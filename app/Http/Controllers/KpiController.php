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
    public function index()
    {
        $stores  = Store::orderBy('code')->get();
        $configs = KpiConfig::with('store')->orderBy('month', 'desc')->get();

        // Nếu đã có config → redirect thẳng vào show của config mới nhất
        if ($configs->isNotEmpty()) {
            return redirect()->route('fe.kpi-config.show', $configs->first()->id);
        }

        return view('kpis.index', compact('stores', 'configs'));
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
            $earlyRatio = (float)($request->weekday_ratio ?? 1.0);
            $lateRatio  = (float)($request->weekend_ratio ?? 1.5);

            // daily_ratios: key là dayOfWeek của Carbon (1=T2, ..., 5=T6, 6=T7, 7=CN)
            $totalRaw = ($earlyRatio * 4) + ($lateRatio * 3); // 4 ngày early, 3 ngày late
            $dailyRatios = [
                1 => round($earlyRatio / $totalRaw * 100, 4), // T2
                2 => round($earlyRatio / $totalRaw * 100, 4), // T3
                3 => round($earlyRatio / $totalRaw * 100, 4), // T4
                4 => round($earlyRatio / $totalRaw * 100, 4), // T5
                5 => round($lateRatio  / $totalRaw * 100, 4), // T6
                6 => round($lateRatio  / $totalRaw * 100, 4), // T7
                7 => round($lateRatio  / $totalRaw * 100, 4), // CN
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
                ->with('success', 'Đã khởi tạo KPI! Kiểm tra lại tỷ trọng tuần bên dưới và lưu.');
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

        // ── Dữ liệu cho switcher ──
        $stores  = Store::orderBy('code')->get();
        $configs = KpiConfig::with('store')->orderBy('month', 'desc')->get();

        return view('kpis.show', compact(
            'config', 'weeks', 'actualByDate',
            'dailyRatios', 'weeklyRatios',
            'stores', 'configs'
        ));
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

        foreach ($weekGroups as $weekNum => $dates) {
            $weekWeight = $weeklyRatios[$weekNum] ?? 20.0;
            $weekTarget = ($config->total_target * $weekWeight) / 100;

            // Tổng day_weight chỉ của các ngày thực tế có trong tuần này
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
                    'week_number'       => $weekNum,
                    'week_weight'       => $weekWeight,
                    'day_weight'        => round($dayWeight, 4),
                    'target_amount'     => round($dayTarget, 2),
                    'rebalanced_target' => round($dayTarget, 2),
                ]);
            }
        }
    }
}
