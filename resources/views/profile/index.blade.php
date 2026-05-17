@extends('layouts.app')
@section('title', 'Hồ sơ — ' . $user->full_name . ' — ' . $month)

@section('content')
@php
    $isSales   = $user->position && $user->position->is_sales;
    $kpiColor  = $personalKpiPct >= 100 ? 'text-emerald-600' : ($personalKpiPct >= 90 ? 'text-amber-500' : 'text-rose-500');
@endphp

{{-- Admin viewing banner --}}
@if($isViewingOther)
<div class="mb-4 flex items-center justify-between bg-blue-50 border border-blue-200 rounded-xl px-4 py-2.5">
    <div class="flex items-center gap-2 text-blue-700 text-sm font-medium">
        <span class="text-base">👁</span>
        Bạn đang xem hồ sơ của <strong>{{ $user->full_name }}</strong> với quyền Admin
    </div>
    <a href="{{ route('fe.users.index') }}"
        class="px-3 py-1 bg-blue-700 text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition-all">
        ← Về danh sách NV
    </a>
</div>
@endif

{{-- ══ HEADER PROFILE ══ --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-5 overflow-hidden">
    <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 flex flex-wrap items-center gap-5">
        {{-- Avatar --}}
        <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center text-white text-2xl font-black shrink-0">
            {{ mb_substr($user->full_name, 0, 1) }}
        </div>
        {{-- Info --}}
        <div class="flex-1 min-w-0">
            <h1 class="text-white font-black text-xl leading-tight">{{ $user->full_name }}</h1>
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <span class="text-[10px] text-slate-300 font-bold uppercase">{{ $user->position->name ?? 'Nhân viên' }}</span>
                <span class="text-slate-500">·</span>
                <span class="text-[10px] text-slate-300">{{ $user->store->name ?? '—' }}</span>
                <span class="text-slate-500">·</span>
                <span class="px-2 py-0.5 rounded text-[9px] font-black {{ $user->contract_type === 'TV' ? 'bg-orange-400/30 text-orange-200' : 'bg-blue-400/30 text-blue-200' }}">
                    {{ $user->contract_type === 'TV' ? 'Thời vụ' : 'Chính thức' }}
                </span>
                @if($hourlyRate > 0)
                <span class="text-[10px] text-slate-400">{{ number_format($hourlyRate, 0, ',', '.') }}đ/h</span>
                @endif
            </div>
        </div>
        {{-- Month picker --}}
        <form method="GET" action="{{ route('fe.profile') }}">
            @if($isViewingOther)
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            @endif
            <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Tháng xem</label>
            <input type="month" name="month" value="{{ $month }}"
                class="px-3 py-1.5 rounded-lg bg-white/10 text-white border border-white/20 outline-none font-bold text-sm"
                onchange="this.form.submit()">
        </form>
    </div>

    {{-- ── KPI + Lương summary ── --}}
    <div class="grid grid-cols-2 md:grid-cols-5 divide-x divide-slate-100">
        {{-- KPI % --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KPI Cá nhân</p>
            @if($isSales)
            <p class="font-black text-3xl leading-none {{ $kpiColor }}">{{ $personalKpiPct }}%</p>
            <p class="text-[8px] text-slate-400 mt-1">T: {{ number_format($totalTarget, 0, ',', '.') }}đ</p>
            @else
            <p class="text-slate-300 font-bold text-lg">N/A</p>
            @endif
        </div>

        {{-- Công/Giờ --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Công / Giờ</p>
            <p class="font-black text-xl text-blue-700">{{ $workDays }} <span class="text-sm font-bold">ngày</span></p>
            <p class="text-[10px] text-blue-500 font-bold mt-0.5">{{ number_format($totalHours, 1) }}h tổng</p>
        </div>

        {{-- DT cá nhân --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">DT Cá nhân</p>
            @if($isSales)
            <p class="font-black text-xl text-emerald-700">{{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <p class="text-[8px] text-slate-400 mt-0.5">đồng</p>
            @else
            <span class="text-slate-300">—</span>
            @endif
        </div>

        {{-- Lương cứng --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Lương cứng</p>
            <p class="font-black text-xl text-slate-700">{{ number_format($baseSalary, 0, ',', '.') }}</p>
            @if($hourlyRate > 0)
            <p class="text-[8px] text-slate-400 mt-0.5">{{ number_format($totalHours, 1) }}h × {{ number_format($hourlyRate, 0, ',', '.') }}</p>
            @endif
        </div>

        {{-- Thực lĩnh --}}
        <div class="px-5 py-4 text-center bg-emerald-50/50">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Dự kiến nhận</p>
            <p class="font-black text-2xl text-emerald-700">{{ number_format($totalSalary, 0, ',', '.') }}</p>
            <p class="text-[8px] text-slate-400 mt-0.5">đồng</p>
        </div>
    </div>
</div>

{{-- ══ SALARY BREAKDOWN ══ --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    {{-- Lương cứng card --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 text-sm">⏱</div>
            <span class="font-bold text-slate-700 text-sm">Lương cứng</span>
        </div>
        <div class="text-2xl font-black text-slate-800 mb-1">{{ number_format($baseSalary, 0, ',', '.') }}đ</div>
        <div class="text-[10px] text-slate-500">{{ number_format($totalHours, 1) }}h × {{ number_format($hourlyRate, 0, ',', '.') }}đ/giờ</div>
    </div>

    {{-- Hoa hồng card --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 text-sm">💰</div>
            <span class="font-bold text-slate-700 text-sm">Hoa hồng</span>
        </div>
        @if($isSales)
        <div class="text-2xl font-black text-purple-700 mb-1">{{ number_format($commission, 0, ',', '.') }}đ</div>
        <div class="text-[10px] text-slate-500">
            {{ number_format($totalRevenue, 0, ',', '.') }} × {{ $commRate }}%
            @if($personalKpiPct < 90)
            <span class="text-rose-400 ml-1">(KPI &lt; 90% → 0%)</span>
            @endif
        </div>
        @else
        <div class="text-slate-300 text-lg font-bold">Non-sales</div>
        @endif
    </div>

    {{-- Thưởng team card --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 text-sm">🏆</div>
            <span class="font-bold text-slate-700 text-sm">Thưởng Team</span>
        </div>
        @if($teamBonus > 0)
        <div class="text-2xl font-black text-amber-600 mb-1">{{ number_format($teamBonus, 0, ',', '.') }}đ</div>
        <div class="text-[10px] text-slate-500">KPI cửa hàng ≥ 90%</div>
        @else
        <div class="text-slate-400 text-sm font-bold mt-2">—</div>
        <div class="text-[10px] text-slate-400">Chưa đủ điều kiện</div>
        @endif
    </div>
</div>

{{-- ══ BẢNG CÔNG THEO NGÀY ══ --}}
@if(count($dailyData) > 0)
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-black text-slate-800 text-sm">📅 Bảng công tháng {{ $month }}</h2>
        <span class="text-[10px] text-slate-400">{{ $workDays }} ngày công · {{ number_format($totalHours, 1) }}h</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-[10px]">
            <thead class="bg-slate-800 text-white text-[9px] font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-2.5">Ngày</th>
                    <th class="px-3 py-2.5 text-center bg-blue-900">☀ Sáng (h)</th>
                    <th class="px-3 py-2.5 text-center bg-blue-900">🌤 Chiều (h)</th>
                    <th class="px-3 py-2.5 text-center bg-blue-900">🌙 Tối (h)</th>
                    <th class="px-3 py-2.5 text-center bg-blue-800 border-r border-blue-700">Tổng giờ</th>
                    @if($isSales)
                    <th class="px-3 py-2.5 text-right bg-emerald-900">DT Sáng</th>
                    <th class="px-3 py-2.5 text-right bg-emerald-900">DT Chiều</th>
                    <th class="px-3 py-2.5 text-right bg-emerald-900">DT Tối</th>
                    <th class="px-3 py-2.5 text-right bg-emerald-800 border-r border-emerald-700">Tổng DT</th>
                    <th class="px-3 py-2.5 text-right bg-amber-800">Target</th>
                    <th class="px-3 py-2.5 text-center bg-amber-800">KPI%</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($dailyData as $date => $d)
                @php
                    $dow     = $d['dow'];
                    $isWeekend = Carbon\Carbon::parse($date)->isWeekend();
                    $kpiC    = $d['kpi_pct'] >= 100 ? 'text-emerald-600 font-black' : ($d['kpi_pct'] >= 90 ? 'text-amber-500 font-bold' : 'text-rose-500');
                    $mSr     = $d['shifts']['morning']   ?? null;
                    $aSr     = $d['shifts']['afternoon'] ?? null;
                    $eSr     = $d['shifts']['evening']   ?? null;
                @endphp
                <tr class="{{ $isWeekend ? 'bg-amber-50/30' : 'bg-white' }} hover:bg-blue-50/20 transition-colors">
                    {{-- Ngày --}}
                    <td class="px-4 py-2 font-bold">
                        <span class="text-slate-800">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</span>
                        <span class="text-[9px] {{ $isWeekend ? 'text-amber-500 font-black' : 'text-slate-400' }} ml-1">{{ $dow }}</span>
                    </td>
                    {{-- Giờ từng ca --}}
                    <td class="px-3 py-2 text-center bg-blue-50/20 {{ $mSr && $mSr->hours > 0 ? 'text-blue-700 font-bold' : 'text-slate-300' }}">
                        {{ $mSr && $mSr->hours > 0 ? number_format($mSr->hours, 1) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-blue-50/20 {{ $aSr && $aSr->hours > 0 ? 'text-blue-700 font-bold' : 'text-slate-300' }}">
                        {{ $aSr && $aSr->hours > 0 ? number_format($aSr->hours, 1) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-blue-50/20 {{ $eSr && $eSr->hours > 0 ? 'text-blue-700 font-bold' : 'text-slate-300' }}">
                        {{ $eSr && $eSr->hours > 0 ? number_format($eSr->hours, 1) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-blue-50/20 border-r border-blue-100 font-black text-blue-900">
                        {{ $d['total_hours'] > 0 ? number_format($d['total_hours'], 1) : '—' }}
                    </td>
                    @if($isSales)
                    {{-- DT từng ca --}}
                    <td class="px-3 py-2 text-right bg-emerald-50/10 {{ $mSr && $mSr->personal_revenue > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $mSr && $mSr->personal_revenue > 0 ? number_format($mSr->personal_revenue, 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-emerald-50/10 {{ $aSr && $aSr->personal_revenue > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $aSr && $aSr->personal_revenue > 0 ? number_format($aSr->personal_revenue, 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-emerald-50/10 {{ $eSr && $eSr->personal_revenue > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $eSr && $eSr->personal_revenue > 0 ? number_format($eSr->personal_revenue, 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-emerald-50/10 border-r border-emerald-100 font-black text-emerald-900">
                        {{ $d['total_revenue'] > 0 ? number_format($d['total_revenue'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-amber-50/20 text-slate-500">
                        {{ $d['target'] > 0 ? number_format($d['target'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-amber-50/20">
                        @if($d['kpi_pct'] > 0)
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black {{ $d['kpi_pct'] >= 100 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600' }}">
                            {{ $d['kpi_pct'] }}%
                        </span>
                        @else
                        <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            {{-- Footer --}}
            <tfoot class="bg-slate-900 text-white text-[9px] font-bold">
                <tr>
                    <td class="px-4 py-2 uppercase">Tổng tháng</td>
                    <td colspan="3" class="px-3 py-2 text-center bg-blue-950">—</td>
                    <td class="px-3 py-2 text-center bg-blue-950 border-r border-blue-800 text-blue-200">
                        {{ number_format($totalHours, 1) }}h
                    </td>
                    @if($isSales)
                    <td colspan="3" class="px-3 py-2 bg-emerald-950 text-emerald-300">—</td>
                    <td class="px-3 py-2 text-right bg-emerald-950 border-r border-emerald-800 text-emerald-200">
                        {{ number_format($totalRevenue, 0, ',', '.') }}đ
                    </td>
                    <td class="px-3 py-2 text-right bg-amber-950 text-amber-200">
                        {{ number_format($totalTarget, 0, ',', '.') }}đ
                    </td>
                    <td class="px-3 py-2 text-center bg-amber-950">
                        <span class="{{ $personalKpiPct >= 100 ? 'text-emerald-300' : ($personalKpiPct >= 90 ? 'text-amber-300' : 'text-rose-300') }} font-black">
                            {{ $personalKpiPct }}%
                        </span>
                    </td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@else
<div class="bg-white p-10 rounded-2xl border-2 border-dashed border-slate-200 text-center">
    <div class="text-4xl mb-3">📭</div>
    <p class="font-bold text-slate-500">Chưa có dữ liệu công tháng {{ $month }}</p>
    <p class="text-xs text-slate-400 mt-1">Hãy kiểm tra lại hoặc chọn tháng khác</p>
</div>
@endif

@endsection
