<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\KpiConfig;
use App\Models\DailyTarget;
use App\Models\ShiftRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyWorkController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $storeId = $request->get('store_id');
        $stores = Store::orderBy('code')->get();
        
        if (auth()->user()->role === 'store_manager' && !$storeId) {
            $storeId = auth()->user()->store_id;
        }

        $users = collect(); // Luôn là Collection để tránh lỗi sum() on array
        $userRecords = collect();
        $dailyTarget = null;
        $isLocked = false;

        if ($storeId) {
            $users = User::with('position')->where('store_id', $storeId)->get();
            $monthStr = Carbon::parse($date)->format('Y-m');
            $dailyTarget = DailyTarget::whereHas('kpiConfig', function($q) use ($storeId, $monthStr) {
                $q->where('store_id', $storeId)->where('month', $monthStr);
            })->where('date', $date)->first();

            $isLocked = ShiftRecord::where('store_id', $storeId)->where('date', $date)->where('is_locked', true)->exists();
            
            // Lấy tất cả records của ngày hôm đó
            $userRecords = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
            
            foreach ($users as $user) {
                $user->shifts = $userRecords->where('user_id', $user->id)->keyBy('shift_type');
            }
        }

        return view('daily.index', compact('stores', 'storeId', 'date', 'users', 'dailyTarget', 'isLocked', 'userRecords'));
    }

    public function updateField(Request $request)
    {
        $request->validate(['user_id' => 'required', 'store_id' => 'required', 'date' => 'required', 'shift_type' => 'required', 'field' => 'required']);
        
        ShiftRecord::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date, 'shift_type' => $request->shift_type],
            ['store_id' => $request->store_id, $request->field => $request->value]
        );

        if ($request->field === 'personal_revenue' || $request->field === 'hours') {
            $this->recalculateTargets($request->store_id, $request->date);
        }

        return response()->json(['status' => 'success']);
    }

    private function recalculateTargets($storeId, $date)
    {
        $monthStr = Carbon::parse($date)->format('Y-m');
        $dailyTarget = DailyTarget::whereHas('kpiConfig', function($q) use ($storeId, $monthStr) {
            $q->where('store_id', $storeId)->where('month', $monthStr);
        })->where('date', $date)->first();
        
        $kpiNgayStore = $dailyTarget ? ($dailyTarget->rebalanced_target ?? $dailyTarget->target_amount) : 0;
        $isWeekend = Carbon::parse($date)->isWeekend();
        $ratios = $isWeekend ? ['morning' => 0.12, 'afternoon' => 0.45, 'evening' => 0.43] : ['morning' => 0.10, 'afternoon' => 0.36, 'evening' => 0.54];

        $records = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
        $users = User::with('position')->where('store_id', $storeId)->get()->keyBy('id');
        
        $totalHoursPerShift = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
        foreach ($records as $record) {
            $user = $users[$record->user_id] ?? null;
            if ($user && $user->position && $user->position->is_sales) {
                $totalHoursPerShift[$record->shift_type] += $record->hours;
            }
        }

        foreach ($records as $record) {
            $user = $users[$record->user_id] ?? null;
            if ($user && $user->position && $user->position->is_sales && $totalHoursPerShift[$record->shift_type] > 0) {
                $firstRec = ShiftRecord::where('user_id', $record->user_id)->where('date', $date)->orderBy('id')->first();
                if ($firstRec && $firstRec->id == $record->id) {
                    $totalUserTarget = 0;
                    $userShifts = $records->where('user_id', $record->user_id);
                    foreach($userShifts as $us) {
                        $st = $kpiNgayStore * $ratios[$us->shift_type];
                        $totalUserTarget += ($us->hours / $totalHoursPerShift[$us->shift_type]) * $st;
                    }
                    $firstRec->target_amount = $totalUserTarget;
                    $totalUserRevenue = $userShifts->sum('personal_revenue');
                    $firstRec->kpi_percentage = ($totalUserTarget > 0) ? ($totalUserRevenue / $totalUserTarget * 100) : 0;
                    $firstRec->save();
                }
            }
        }
    }

    public function equalize(Request $request)
    {
        $date = $request->date;
        $storeId = $request->store_id;
        $totalRevenue = $request->total_revenue ?? 0;

        DB::beginTransaction();
        try {
            // 1. Lấy shift ratio từ KpiConfig (thay vì hardcode)
            $monthStr = Carbon::parse($date)->format('Y-m');
            $kpiConfig = \App\Models\KpiConfig::where('store_id', $storeId)->where('month', $monthStr)->first();
            $ratios = $kpiConfig
                ? $kpiConfig->getShiftRatioForDate($date)
                : (Carbon::parse($date)->isoWeekday() >= 6
                    ? ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
                    : ['morning' => 10, 'afternoon' => 36, 'evening' => 54]);
            // Normalize ratios thành phần trăm (tổng = 100)
            $ratioTotal = array_sum($ratios);
            $ratios = array_map(fn($r) => $ratioTotal > 0 ? $r / $ratioTotal : 0, $ratios);

            $records = ShiftRecord::where('store_id', $storeId)->where('date', $date)->get();
            $users = User::with('position')->where('store_id', $storeId)->get()->keyBy('id');
            
            $totalHoursPerShift = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
            foreach ($records as $record) {
                $user = $users[$record->user_id] ?? null;
                if ($user && $user->position && $user->position->is_sales) {
                    $totalHoursPerShift[$record->shift_type] += $record->hours;
                }
            }

            foreach ($records as $record) {
                $user = $users[$record->user_id] ?? null;
                if ($user && $user->position && $user->position->is_sales && $totalHoursPerShift[$record->shift_type] > 0) {
                    $shiftRevenue = $totalRevenue * $ratios[$record->shift_type];
                    $record->personal_revenue = ($record->hours / $totalHoursPerShift[$record->shift_type]) * $shiftRevenue;
                    $record->shift_revenue = $shiftRevenue;
                    $record->save();
                }
            }

            $this->recalculateTargets($storeId, $date);
            $this->rebalanceWeekly($storeId, $date, $totalRevenue);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Đã cân bằng KPI chuẩn!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function rebalanceWeekly($storeId, $date, $actualRevenue)
    {
        $carbonDate = Carbon::parse($date);
        $startOfWeek = $carbonDate->copy()->startOfWeek();
        $endOfWeek = $carbonDate->copy()->endOfWeek();
        $weeklyTargets = DailyTarget::whereHas('kpiConfig', function($q) use ($storeId) { $q->where('store_id', $storeId); })
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])->get();
        $weeklyTargetTotal = $weeklyTargets->sum('target_amount');
        $pastActualTotal = ShiftRecord::where('store_id', $storeId)
            ->whereBetween('date', [$startOfWeek->toDateString(), $date])
            ->select('date', DB::raw('SUM(DISTINCT shift_revenue) as daily_total'))
            ->groupBy('date')->get()->sum('daily_total');
        $remaining = max(0, $weeklyTargetTotal - $pastActualTotal);
        $futureDaysInWeek = $weeklyTargets->where('date', '>', $date);
        if ($futureDaysInWeek->count() > 0) {
            $totalFutureWeight = $futureDaysInWeek->sum(function($d) { $dw = Carbon::parse($d->date)->dayOfWeek; return ($dw == 5 || $dw == 6 || $dw == 0) ? 1.5 : 1.0; });
            foreach ($futureDaysInWeek as $ft) { $dw = Carbon::parse($ft->date)->dayOfWeek; $dayWeight = ($dw == 5 || $dw == 6 || $dw == 0) ? 1.5 : 1.0; $ft->rebalanced_target = ($remaining * $dayWeight) / $totalFutureWeight; $ft->save(); }
        }
    }

    public function lock(Request $request) {
        ShiftRecord::where('store_id', $request->store_id)->where('date', $request->date)->update(['is_locked' => true]);
        return response()->json(['status' => 'success']);
    }
}
