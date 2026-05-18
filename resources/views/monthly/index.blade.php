@extends('layouts.app')
@section('title', 'Tổng quan tháng ' . $month)
@section('has_local_alert', true)
@section('content')

{{-- Custom Local Alerts --}}
@if(session('success'))
<div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl text-xs font-bold text-emerald-800 shadow-sm flex items-center gap-2">
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl text-xs font-bold text-rose-800 shadow-sm flex items-center gap-2">
    <span>{{ session('error') }}</span>
</div>
@endif

{{-- ── Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-black text-slate-800">📋 Bảng công tháng</h1>
        <p class="text-xs text-slate-400 mt-0.5">Cal_Bảng công — toàn bộ ca làm theo ngày</p>
    </div>
    @if($selectedStore)
    <a href="{{ route('fe.monthly.revenue', ['store' => $selectedStore->id, 'month' => $month]) }}"
        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-sm">
        📈 Bảng doanh thu
    </a>
    @endif
</div>

{{-- ── Filter bar ── --}}
<form method="GET" action="{{ route('fe.monthly.index') }}"
    class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4 mb-5 flex flex-wrap items-end gap-4">

    {{-- Cửa hàng --}}
    <div class="flex-1 min-w-[200px]">
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Cửa hàng</label>
        <select name="store_id" id="select-store" style="width:100%">
            <option value="">— Chọn cửa hàng —</option>
            @foreach($stores as $st)
            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                {{ $st->name }} ({{ $st->code }})
            </option>
            @endforeach
        </select>
    </div>

    {{-- Nhân viên (chỉ hiện khi đã chọn store) --}}
    <div class="flex-1 min-w-[200px]">
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Nhân viên</label>
        <select name="user_id" id="select-user" style="width:100%" {{ !$storeId ? 'disabled' : '' }}>
            <option value="">— Tất cả nhân viên —</option>
            @foreach($allUsers as $u)
            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                {{ $u->full_name }} ({{ $u->username }})
            </option>
            @endforeach
        </select>
    </div>

    {{-- Tháng --}}
    <div>
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Tháng</label>
        <input type="month" name="month" value="{{ $month }}"
            class="px-3 py-2 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm">
    </div>

    {{-- Tuần --}}
    <div>
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Tuần</label>
        <select name="week_num"
            class="px-3 py-2 rounded-lg border border-slate-200 outline-none font-medium text-slate-700 text-sm bg-white">
            <option value="">— Tất cả tuần —</option>
            @for($w = 1; $w <= 52; $w++)
            <option value="{{ $w }}" {{ ($weekNum ?? '') == $w ? 'selected' : '' }}>Tuần {{ $w }}</option>
            @endfor
        </select>
    </div>

    {{-- Từ ngày --}}
    <div>
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Từ ngày</label>
        <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}"
            class="px-3 py-2 rounded-lg border border-slate-200 outline-none font-medium text-slate-700 text-sm">
    </div>

    {{-- Đến ngày --}}
    <div>
        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1.5">Đến ngày</label>
        <input type="date" name="date_to" value="{{ $dateTo ?? '' }}"
            class="px-3 py-2 rounded-lg border border-slate-200 outline-none font-medium text-slate-700 text-sm">
    </div>

    <button type="submit"
        class="px-5 py-2 rounded-lg bg-slate-800 text-white font-bold text-sm hover:bg-slate-700 transition-all">
        Lọc
    </button>

    @if($userId || $weekNum || $dateFrom || $dateTo)
    <a href="{{ route('fe.monthly.index', array_filter(['store_id' => $storeId, 'month' => $month])) }}"
        class="px-4 py-2 rounded-lg bg-rose-50 text-rose-500 border border-rose-100 font-bold text-sm hover:bg-rose-100 transition-all">
        ✕ Xoá bộ lọc
    </a>
    @endif
</form>

@if(!$storeId)
{{-- BẢNG GIÁM SÁT & CHỐT DOANH THU TOÀN CHUỖI --}}
<div class="mb-6">
    <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">📊 Bảng giám sát & Đối chiếu doanh thu</h2>
    
    {{-- Grid metric tổng --}}
    @php
        $totalTgt = collect($storeSummaries)->sum('target');
        $totalRep = collect($storeSummaries)->sum('reported_revenue');
        $totalApi = collect($storeSummaries)->sum('api_revenue');
        $totalPct = $totalTgt > 0 ? round($totalRep / $totalTgt * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Target Toàn Chuỗi</p>
            <p class="font-black text-xl text-slate-700">{{ $totalTgt > 0 ? number_format($totalTgt/1e6, 1).'M' : '—' }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">DT Thực Tế (Nhập tay)</p>
            <p class="font-black text-xl text-emerald-700">{{ number_format($totalRep/1e6, 1) }}M</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">DT Thực Tế (POS/API)</p>
            <p class="font-black text-xl text-indigo-700">{{ number_format($totalApi/1e6, 1) }}M</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Hiệu Suất Chuỗi</p>
            <p class="font-black text-xl text-blue-700">{{ $totalPct }}%</p>
        </div>
    </div>

    {{-- Bảng chi tiết đối chiếu cửa hàng --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                🏪 Đối chiếu & Khoá dữ liệu doanh thu
            </h3>
            <span class="text-[10px] text-slate-400 font-bold">Tháng {{ $month }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Cửa hàng</th>
                        <th class="px-4 py-3 text-right">Target</th>
                        <th class="px-4 py-3 text-right">DT Nhập Tay</th>
                        <th class="px-4 py-3 text-right bg-slate-900/10">DT API (Nhanh.vn)</th>
                        <th class="px-4 py-3 text-center">Hoàn Thành KPI</th>
                        <th class="px-4 py-3 text-center">Cờ Chênh Lệch (>5%)</th>
                        <th class="px-4 py-3 text-center">Khoá Tháng (Admin/HR)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($storeSummaries as $sum)
                    @php
                        $st = $sum['store'];
                        $kpiPctColor = $sum['kpi_pct'] >= 100 ? 'text-emerald-600 font-black' : ($sum['kpi_pct'] >= 90 ? 'text-amber-500 font-bold' : 'text-rose-500');
                    @endphp
                    <tr class="hover:bg-blue-50/10 transition-colors">
                        {{-- Cửa hàng --}}
                        <td class="px-5 py-3.5">
                            <a href="{{ route('fe.monthly.index', ['store_id' => $st->id, 'month' => $month]) }}" class="font-bold text-indigo-600 hover:text-indigo-800 text-xs flex items-center gap-1.5">
                                🏪 {{ $st->name }}
                                <span class="px-2 py-0.5 border border-slate-200 bg-slate-50 rounded text-[9px] font-black text-slate-500 uppercase tracking-wider">
                                    {{ $st->code }}
                                </span>
                            </a>
                        </td>
                        
                        {{-- Target --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs text-slate-500">
                            {{ $sum['target'] > 0 ? number_format($sum['target'], 0, ',', '.') . 'đ' : '—' }}
                        </td>

                        {{-- DT Nhập tay --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-bold text-emerald-700">
                            {{ number_format($sum['reported_revenue'], 0, ',', '.') }}đ
                        </td>

                        {{-- DT API --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-bold text-indigo-700 bg-slate-50/50">
                            {{ number_format($sum['api_revenue'], 0, ',', '.') }}đ
                        </td>

                        {{-- Hoàn thành KPI --}}
                        <td class="px-4 py-3.5 text-center">
                            @if($sum['target'] > 0)
                            <span class="text-xs {{ $kpiPctColor }}">{{ $sum['kpi_pct'] }}%</span>
                            <div class="w-24 bg-slate-100 rounded-full h-1 mt-1 mx-auto">
                                <div class="h-1 rounded-full {{ $sum['kpi_pct'] >= 100 ? 'bg-emerald-500' : ($sum['kpi_pct'] >= 90 ? 'bg-amber-400' : 'bg-rose-400') }}"
                                    style="width: {{ min(100, $sum['kpi_pct']) }}%"></div>
                            </div>
                            @else
                            <span class="text-xs text-slate-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Cờ chênh lệch --}}
                        <td class="px-4 py-3.5 text-center">
                            @if($sum['reported_revenue'] > 0)
                                @if($sum['is_discrepancy'])
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 border border-rose-100 text-[10px] font-black text-rose-600 animate-pulse" title="Lệch quá 5% cho phép!">
                                    🚨 Lệch {{ $sum['diff_pct'] }}%
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-[10px] font-bold text-emerald-600">
                                    ✅ Khớp ({{ $sum['diff_pct'] }}%)
                                </span>
                                @endif
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10px] text-slate-400">
                                No data
                            </span>
                            @endif
                        </td>

                        {{-- Khoá tháng --}}
                        <td class="px-4 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($sum['is_month_locked'])
                                <span class="px-2 py-1 bg-rose-50 border border-rose-100 rounded-lg text-[10px] font-black text-rose-600 flex items-center gap-1">
                                    🔒 Đã khoá
                                </span>
                                @else
                                <span class="px-2 py-1 bg-emerald-50 border border-emerald-100 rounded-lg text-[10px] font-bold text-emerald-600 flex items-center gap-1">
                                    🔓 Mở
                                </span>

                                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'store_manager')
                                <form action="{{ route('fe.monthly.toggle-lock', $st->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="action" value="lock">
                                    <button type="submit" onclick="return confirm('Khoá bảng công toàn bộ cửa hàng {{ $st->name }} trong tháng {{ $month }}? Mọi ca làm sẽ ở chế độ Read-only.')"
                                             class="px-2 py-1 bg-slate-900 hover:bg-rose-600 text-white text-[10px] font-bold rounded-lg active:scale-95 transition-all">
                                        🔒 Khoá tháng
                                    </button>
                                </form>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else

{{-- ── Summary Cards ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng DT {{ $selectedUser ? 'cá nhân' : 'cửa hàng' }}</p>
        <p class="font-black text-xl text-emerald-700">{{ $grandTotalDT > 0 ? number_format($grandTotalDT/1000000,1).'M' : '—' }}</p>
        @if($storeTarget > 0)
        <div class="mt-2">
            <div class="flex justify-between text-[9px] mb-1">
                <span class="text-slate-400">Target: {{ number_format($storeTarget/1000000,1) }}M</span>
                <span class="font-bold {{ $kpiPctStore >= 100 ? 'text-emerald-600' : ($kpiPctStore >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
                    {{ $kpiPctStore }}%
                </span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $kpiPctStore >= 100 ? 'bg-emerald-500' : ($kpiPctStore >= 90 ? 'bg-amber-400' : 'bg-rose-400') }}"
                    style="width: {{ min(100, $kpiPctStore) }}%"></div>
            </div>
        </div>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng giờ làm</p>
        <p class="font-black text-xl text-indigo-700">{{ number_format($grandTotalHours, 1) }}<span class="text-sm font-medium text-slate-400">h</span></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Số dòng dữ liệu</p>
        <p class="font-black text-xl text-slate-700">{{ count($rows) }}</p>
        <p class="text-[9px] text-slate-400">ca làm có ghi nhận</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        @if($selectedUser)
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Nhân viên</p>
        <p class="font-bold text-sm text-slate-800 truncate">{{ $selectedUser->full_name }}</p>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="text-[9px] text-slate-500">{{ $selectedUser->position?->name ?? '—' }}</span>
            <span class="text-[8px] px-1.5 py-0.5 rounded font-bold {{ $selectedUser->contract_type === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                {{ $selectedUser->contract_type ?? 'CT' }}
            </span>
        </div>
        @else
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Nhân sự có data</p>
        <p class="font-black text-xl text-blue-700">{{ collect($rows)->pluck('user.id')->unique()->count() }}</p>
        <p class="text-[9px] text-slate-400">/ {{ $allUsers->count() }} nhân sự</p>
        @endif
    </div>
</div>

{{-- ── Main Table ── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-black text-slate-800 text-sm">
            {{ $selectedUser ? '👤 ' . $selectedUser->full_name : '👥 Toàn bộ nhân viên' }}
            — {{ $selectedStore->name }} — {{ $month }}
        </h2>
        <div class="flex items-center gap-3">
            <span class="text-[10px] text-slate-400">{{ count($rows) }} dòng</span>
            @if($userId)
            <a href="{{ route('fe.monthly.index', ['store_id' => $storeId, 'month' => $month]) }}"
                class="text-[10px] font-bold text-rose-500 hover:text-rose-700">✕ Bỏ lọc NV</a>
            @endif
        </div>
    </div>

    @if(count($rows) === 0)
    <div class="px-5 py-16 text-center">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-slate-400 font-medium">Chưa có dữ liệu bảng công tháng {{ $month }}</p>
        <p class="text-xs text-slate-300 mt-1">Nhân viên cần nhập ca làm tại Bảng công ngày</p>
    </div>
    @else
    <div class="overflow-x-auto" style="max-height:70vh; overflow-y:auto;">
        <table class="w-full text-left border-collapse text-sm" style="min-width:1100px">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider sticky top-0 z-20">
                {{-- Row 1: column headers --}}
                <tr>
                    <th class="px-3 py-3 text-center w-8">#</th>
                    <th class="px-3 py-3 w-24">Ngày</th>
                    <th class="px-2 py-3 text-center w-8">T.</th>
                    @if(!$selectedUser)
                    <th class="px-4 py-3 min-w-[140px]">Họ và tên</th>
                    <th class="px-3 py-3 text-center">ID</th>
                    <th class="px-3 py-3 text-center">Chức danh</th>
                    <th class="px-2 py-3 text-center">HĐ</th>
                    @endif
                    <th class="px-3 py-3 text-center bg-blue-900/30">GC Sáng</th>
                    <th class="px-3 py-3 text-center bg-amber-900/20">GC Chiều</th>
                    <th class="px-3 py-3 text-center bg-slate-700/40">GC Tối</th>
                    <th class="px-3 py-3 text-center bg-purple-900/20">GC BS</th>
                    <th class="px-4 py-3 text-right bg-blue-900/20">DTCN Sáng</th>
                    <th class="px-4 py-3 text-right bg-amber-900/15">DTCN Chiều</th>
                    <th class="px-4 py-3 text-right bg-slate-700/30">DTCN Tối</th>
                    <th class="px-2 py-3 text-center">KH</th>
                    <th class="px-2 py-3 text-center">Thử đồ</th>
                    <th class="px-2 py-3 text-center">Đơn</th>
                    <th class="px-2 py-3 text-center">SP</th>
                    <th class="px-3 py-3 text-center">KPI%</th>
                    <th class="px-3 py-3 text-right">Tổng giờ</th>
                    <th class="px-4 py-3 text-right">Tổng DTCN</th>
                    <th class="px-3 py-3 text-center">Tháng</th>
                    <th class="px-3 py-3 text-center">Tuần</th>
                </tr>
                {{-- Row 2: sticky total (clone footer) --}}
                <tr class="bg-emerald-900/90 text-[9px] border-t border-emerald-700">
                    <td class="px-3 py-2 text-emerald-300 font-black" colspan="{{ $selectedUser ? 3 : 7 }}">∑ {{ count($rows) }} dòng</td>
                    <td class="px-3 py-2 text-center text-blue-300">{{ number_format(array_sum(array_column($rows,'gc_sang')),1) }}</td>
                    <td class="px-3 py-2 text-center text-amber-300">{{ number_format(array_sum(array_column($rows,'gc_chieu')),1) }}</td>
                    <td class="px-3 py-2 text-center text-slate-300">{{ number_format(array_sum(array_column($rows,'gc_toi')),1) }}</td>
                    <td class="px-3 py-2 text-center text-purple-300">{{ number_format(array_sum(array_column($rows,'gc_bs')),1) }}</td>
                    <td class="px-4 py-2 text-right text-blue-300">{{ number_format(array_sum(array_column($rows,'dt_sang')),0,',','.') }}</td>
                    <td class="px-4 py-2 text-right text-amber-300">{{ number_format(array_sum(array_column($rows,'dt_chieu')),0,',','.') }}</td>
                    <td class="px-4 py-2 text-right text-slate-300">{{ number_format(array_sum(array_column($rows,'dt_toi')),0,',','.') }}</td>
                    <td class="px-2 py-2 text-center text-amber-300">{{ number_format(array_sum(array_column($rows,'so_kh'))) }}</td>
                    <td class="px-2 py-2 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'thu_do'))) }}</td>
                    <td class="px-2 py-2 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'so_don'))) }}</td>
                    <td class="px-2 py-2 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'so_sp'))) }}</td>
                    <td class="px-3 py-2 text-center text-slate-400">—</td>
                    <td class="px-3 py-2 text-right text-indigo-300 font-black">{{ number_format($grandTotalHours,1) }}h</td>
                    <td class="px-4 py-2 text-right text-emerald-400 font-black">{{ number_format($grandTotalDT,0,',','.') }}</td>
                    <td colspan="2" class="px-3 py-2 text-center text-slate-400">—</td>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $prevDate = null; $rowNum = 0; @endphp
                @foreach($rows as $row)
                @php
                    $rowNum++;
                    $isWeekend = in_array($row['day_of_week'], ['T7', 'CN']);
                    $isNewDate = $prevDate !== $row['date'];
                    $prevDate  = $row['date'];
                    $kpiColor  = $row['kpi_pct'] >= 100 ? 'text-emerald-600 font-black'
                               : ($row['kpi_pct'] >= 90  ? 'text-amber-600 font-bold'
                               : ($row['kpi_pct'] > 0    ? 'text-rose-500' : 'text-slate-300'));
                    $rowBg     = $isWeekend ? 'bg-amber-50/50' : ($loop->even ? 'bg-slate-50/30' : 'bg-white');
                    $isSales   = $row['user']->position?->is_sales ?? false;
                @endphp
                <tr class="{{ $rowBg }} hover:bg-blue-50/40 transition-colors {{ $isNewDate && !$loop->first ? 'border-t-2 border-slate-200' : '' }}">
                    <td class="px-3 py-2 text-center text-[10px] text-slate-300 font-mono">{{ $rowNum }}</td>
                    <td class="px-3 py-2">
                        <div class="font-bold text-slate-800 text-xs">{{ $row['date_fmt'] }}</div>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <span class="text-[10px] font-black {{ $isWeekend ? 'text-amber-600' : 'text-slate-400' }}">{{ $row['day_of_week'] }}</span>
                    </td>

                    @if(!$selectedUser)
                    <td class="px-4 py-2">
                        <a href="{{ route('fe.monthly.index', ['store_id' => $storeId, 'month' => $month, 'user_id' => $row['user']->id]) }}"
                            class="font-bold text-xs text-slate-800 hover:text-indigo-700 hover:underline transition-colors">
                            {{ $row['user']->full_name }}
                        </a>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="text-[9px] font-mono text-slate-400">{{ $row['user']->username }}</span>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="text-[9px] font-bold font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">{{ $row['user']->position?->code ?? '—' }}</span>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <span class="text-[8px] px-1.5 py-0.5 rounded font-bold {{ $row['user']->contract_type === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $row['user']->contract_type ?? 'CT' }}
                        </span>
                    </td>
                    @endif

                    {{-- Giờ công --}}
                    <td class="px-3 py-2 text-center bg-blue-50/20">
                        <span class="text-xs {{ $row['gc_sang'] > 0 ? 'font-bold text-blue-700' : 'text-slate-200' }}">{{ $row['gc_sang'] > 0 ? number_format($row['gc_sang'],1) : '—' }}</span>
                    </td>
                    <td class="px-3 py-2 text-center bg-amber-50/15">
                        <span class="text-xs {{ $row['gc_chieu'] > 0 ? 'font-bold text-amber-700' : 'text-slate-200' }}">{{ $row['gc_chieu'] > 0 ? number_format($row['gc_chieu'],1) : '—' }}</span>
                    </td>
                    <td class="px-3 py-2 text-center bg-slate-50/50">
                        <span class="text-xs {{ $row['gc_toi'] > 0 ? 'font-bold text-slate-700' : 'text-slate-200' }}">{{ $row['gc_toi'] > 0 ? number_format($row['gc_toi'],1) : '—' }}</span>
                    </td>
                    <td class="px-3 py-2 text-center bg-purple-50/20">
                        <span class="text-xs {{ $row['gc_bs'] > 0 ? 'font-bold text-purple-700' : 'text-slate-200' }}">{{ $row['gc_bs'] > 0 ? number_format($row['gc_bs'],1) : '—' }}</span>
                    </td>

                    {{-- DT cá nhân --}}
                    <td class="px-4 py-2 text-right bg-blue-50/10">
                        <span class="text-xs font-mono {{ $row['dt_sang'] > 0 ? 'font-bold text-blue-700' : 'text-slate-200' }}">{{ $row['dt_sang'] > 0 ? number_format($row['dt_sang'],0,',','.') : '—' }}</span>
                    </td>
                    <td class="px-4 py-2 text-right bg-amber-50/10">
                        <span class="text-xs font-mono {{ $row['dt_chieu'] > 0 ? 'font-bold text-amber-700' : 'text-slate-200' }}">{{ $row['dt_chieu'] > 0 ? number_format($row['dt_chieu'],0,',','.') : '—' }}</span>
                    </td>
                    <td class="px-4 py-2 text-right bg-slate-50/50">
                        <span class="text-xs font-mono {{ $row['dt_toi'] > 0 ? 'font-bold text-slate-700' : 'text-slate-200' }}">{{ $row['dt_toi'] > 0 ? number_format($row['dt_toi'],0,',','.') : '—' }}</span>
                    </td>

                    {{-- Số liệu phụ --}}
                    <td class="px-2 py-2 text-center text-xs font-bold text-slate-600">{{ $row['so_kh'] ?: '—' }}</td>
                    <td class="px-2 py-2 text-center text-xs text-slate-500">{{ $row['thu_do'] ?: '—' }}</td>
                    <td class="px-2 py-2 text-center text-xs text-slate-500">{{ $row['so_don'] ?: '—' }}</td>
                    <td class="px-2 py-2 text-center text-xs text-slate-500">{{ $row['so_sp'] ?: '—' }}</td>

                    {{-- KPI% --}}
                    <td class="px-3 py-2 text-center">
                        @if($isSales && $row['kpi_pct'] > 0)
                            <span class="text-xs {{ $kpiColor }}">{{ $row['kpi_pct'] }}%</span>
                        @elseif(!$isSales)
                            <span class="text-[9px] text-slate-300 italic">non</span>
                        @else
                            <span class="text-slate-200 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Tổng giờ --}}
                    <td class="px-3 py-2 text-right">
                        <span class="text-xs font-black text-indigo-700">{{ number_format($row['total_hours'],1) }}</span>
                        <span class="text-[9px] text-slate-400">h</span>
                    </td>

                    {{-- Tổng DTCN --}}
                    <td class="px-4 py-2 text-right">
                        @if($row['total_dt'] > 0)
                        <span class="text-xs font-black text-emerald-700">{{ number_format($row['total_dt'],0,',','.') }}</span>
                        @else
                        <span class="text-slate-200 text-xs">—</span>
                        @endif
                    </td>

                    <td class="px-3 py-2 text-center text-[10px] text-slate-400">{{ $row['month_fmt'] }}</td>
                    <td class="px-3 py-2 text-center text-[10px] text-slate-400">{{ $row['week_label'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-800 text-white text-xs font-bold border-t-2 border-slate-600">
                <tr>
                    <td class="px-3 py-3 text-slate-400" colspan="{{ $selectedUser ? 3 : 7 }}">TỔNG THÁNG ({{ count($rows) }} dòng)</td>
                    <td class="px-3 py-3 text-center text-blue-300">{{ number_format(array_sum(array_column($rows,'gc_sang')),1) }}</td>
                    <td class="px-3 py-3 text-center text-amber-300">{{ number_format(array_sum(array_column($rows,'gc_chieu')),1) }}</td>
                    <td class="px-3 py-3 text-center text-slate-300">{{ number_format(array_sum(array_column($rows,'gc_toi')),1) }}</td>
                    <td class="px-3 py-3 text-center text-purple-300">{{ number_format(array_sum(array_column($rows,'gc_bs')),1) }}</td>
                    <td class="px-4 py-3 text-right text-blue-300">{{ number_format(array_sum(array_column($rows,'dt_sang')),0,',','.') }}</td>
                    <td class="px-4 py-3 text-right text-amber-300">{{ number_format(array_sum(array_column($rows,'dt_chieu')),0,',','.') }}</td>
                    <td class="px-4 py-3 text-right text-slate-300">{{ number_format(array_sum(array_column($rows,'dt_toi')),0,',','.') }}</td>
                    <td class="px-2 py-3 text-center text-amber-300">{{ number_format(array_sum(array_column($rows,'so_kh'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'thu_do'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'so_don'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows,'so_sp'))) }}</td>
                    <td class="px-3 py-3 text-center text-slate-400">—</td>
                    <td class="px-3 py-3 text-right text-indigo-300">{{ number_format($grandTotalHours,1) }}h</td>
                    <td class="px-4 py-3 text-right text-emerald-400 text-sm">{{ number_format($grandTotalDT,0,',','.') }}</td>
                    <td colspan="2" class="px-3 py-3 text-center text-slate-400">— {{ $month }} —</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Select2 cho Cửa hàng
    $('#select-store').select2({
        placeholder: '— Chọn cửa hàng —',
        allowClear: true,
        width: '100%',
        language: {
            searching: function() { return 'Đang tìm...'; },
            noResults: function() { return 'Không tìm thấy'; }
        }
    }).on('select2:select select2:clear', function () {
        // Auto-submit khi chọn cửa hàng
        $(this).closest('form').submit();
    });

    // Select2 cho Nhân viên
    var $userSelect = $('#select-user');
    $userSelect.select2({
        placeholder: '— Tất cả nhân viên —',
        allowClear: true,
        width: '100%',
        @if(!$storeId)
        disabled: true,
        @endif
        language: {
            searching: function() { return 'Đang tìm kiếm...'; },
            noResults: function() { return 'Không tìm thấy nhân viên'; }
        }
    });
});
</script>
@endpush
