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
            <h1 class="text-white font-black text-xl leading-tight flex items-center gap-2">
                {{ $user->full_name }}
                <span class="text-slate-300 font-mono text-xs font-normal">({{ '@' . $user->username }})</span>
            </h1>
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
    @if($isSales)
    <div class="grid grid-cols-2 md:grid-cols-5 divide-x divide-slate-100">
        {{-- KPI % --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KPI Cá nhân</p>
            <p class="font-black text-3xl leading-none {{ $kpiColor }}">{{ $personalKpiPct }}%</p>
            <p class="text-[8px] text-slate-400 mt-1">T: {{ number_format($totalTarget, 0, ',', '.') }}đ</p>
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
            <p class="font-black text-xl text-emerald-700">{{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <p class="text-[8px] text-slate-400 mt-0.5">đồng</p>
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
    @else
    <div class="grid grid-cols-1 md:grid-cols-3 divide-x divide-slate-100">
        {{-- Công/Giờ --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Công / Giờ</p>
            <p class="font-black text-xl text-blue-700">{{ $workDays }} <span class="text-sm font-bold">ngày</span></p>
            <p class="text-[10px] text-blue-500 font-bold mt-0.5">{{ number_format($totalHours, 1) }}h tổng</p>
        </div>

        {{-- Lương cơ bản / Giờ --}}
        <div class="px-5 py-4 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Lương cơ bản / Giờ</p>
            <p class="font-black text-xl text-slate-700">{{ number_format($hourlyRate, 0, ',', '.') }}đ/h</p>
            <p class="text-[8px] text-slate-400 mt-0.5">Mức lương cứng theo giờ làm việc</p>
        </div>

        {{-- Tổng lương dự kiến nhận --}}
        <div class="px-5 py-4 text-center bg-emerald-50/50">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng lương dự kiến</p>
            <p class="font-black text-2xl text-emerald-700">{{ number_format($totalSalary, 0, ',', '.') }}</p>
            <p class="text-[8px] text-slate-400 mt-0.5">đồng (đã bao gồm thưởng team nếu có)</p>
        </div>
    </div>
    @endif
</div>

{{-- ══ SALARY BREAKDOWN ══ --}}
@php
    $showTeamBonusCard = $user->position && $user->position->team_bonus_base > 0;
    $gridCols = 1;
    if ($isSales && $showTeamBonusCard) {
        $gridCols = 3;
    } elseif ($isSales || $showTeamBonusCard) {
        $gridCols = 2;
    } else {
        $gridCols = 1;
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-{{ $gridCols }} gap-4 mb-5">
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
    @if($isSales)
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 text-sm">💰</div>
            <span class="font-bold text-slate-700 text-sm">Hoa hồng</span>
        </div>
        <div class="text-2xl font-black text-purple-700 mb-1">{{ number_format($commission, 0, ',', '.') }}đ</div>
        <div class="text-[10px] text-slate-500">
            {{ number_format($totalRevenue, 0, ',', '.') }} × {{ $commRate }}%
            @if($personalKpiPct < 90)
            <span class="text-rose-400 ml-1">(KPI &lt; 90% → 0%)</span>
            @endif
        </div>
    </div>
    @endif

    {{-- Thưởng team card --}}
    @if($showTeamBonusCard)
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 text-sm">🏆</div>
            <span class="font-bold text-slate-700 text-sm">Thưởng Team</span>
        </div>
        @if($teamBonus > 0)
        <div class="text-2xl font-black text-amber-600 mb-1">{{ number_format($teamBonus, 0, ',', '.') }}đ</div>
        <div class="text-[10px] text-slate-500">KPI cửa hàng đạt chuẩn</div>
        @else
        <div class="text-slate-400 text-sm font-bold mt-2">—</div>
        <div class="text-[10px] text-slate-400">Chưa đủ điều kiện</div>
        @endif
    </div>
    @endif
</div>


@endsection
