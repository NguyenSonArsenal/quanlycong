@extends('layouts.app')
@section('title', 'Tổng quan tháng ' . $month)
@section('content')

{{-- ── Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-black text-slate-800">📊 Tổng quan tháng</h1>
        <p class="text-xs text-slate-400 mt-0.5">KPI & doanh thu toàn hệ thống</p>
    </div>
    <form method="GET" action="{{ route('fe.monthly.index') }}" class="flex items-center gap-3">
        <div>
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tháng</label>
            <input type="month" name="month" value="{{ $month }}"
                class="px-3 py-1.5 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm"
                onchange="this.form.submit()">
        </div>
    </form>
</div>

{{-- ── Grand total cards ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng Target toàn CH</p>
        <p class="font-black text-xl text-slate-700">{{ $grandTarget > 0 ? number_format($grandTarget/1000000, 1).'M' : '—' }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng DT thực tế</p>
        <p class="font-black text-xl text-emerald-700">{{ $grandRevenue > 0 ? number_format($grandRevenue/1000000, 1).'M' : '—' }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KPI Tổng hệ thống</p>
        <p class="font-black text-3xl leading-none {{ $grandKpi >= 100 ? 'text-emerald-600' : ($grandKpi >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
            {{ $grandKpi > 0 ? $grandKpi.'%' : '—' }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Số cửa hàng</p>
        <p class="font-black text-xl text-blue-700">{{ count($stores) }}</p>
        <p class="text-[9px] text-slate-400">{{ collect($storeOverviews)->where('has_config',true)->count() }} đã cấu hình KPI</p>
    </div>
</div>

{{-- ── Store KPI table ── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-slate-100">
        <h2 class="font-black text-slate-800 text-sm">🏪 KPI theo cửa hàng — {{ $month }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-4 py-3">Cửa hàng</th>
                    <th class="px-4 py-3 text-center">Cấu hình KPI</th>
                    <th class="px-4 py-3 text-right">Target tháng</th>
                    <th class="px-4 py-3 text-right">DT thực tế</th>
                    <th class="px-4 py-3 text-right">Chênh lệch</th>
                    <th class="px-4 py-3 text-center">KPI %</th>
                    <th class="px-4 py-3 text-center">Nhân sự</th>
                    <th class="px-4 py-3 text-center">Ngày có data</th>
                    <th class="px-4 py-3 text-center">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($storeOverviews as $ov)
                @php
                    $diff     = $ov['revenue'] - $ov['target'];
                    $kpiColor = $ov['kpi_pct'] >= 100 ? 'bg-emerald-100 text-emerald-700'
                              : ($ov['kpi_pct'] >= 90 ? 'bg-amber-100 text-amber-600'
                              : 'bg-rose-100 text-rose-600');
                @endphp
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-bold text-slate-800">{{ $ov['store']->name }}</div>
                        <div class="text-[9px] text-slate-400 font-mono">{{ $ov['store']->code }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($ov['has_config'])
                        <span class="text-emerald-500 font-bold text-xs">✅ Đã cấu hình</span>
                        @else
                        <span class="text-rose-400 text-xs font-medium">⚠️ Chưa có</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-xs text-slate-600">
                        {{ $ov['target'] > 0 ? number_format($ov['target'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-xs font-bold text-emerald-700">
                        {{ $ov['revenue'] > 0 ? number_format($ov['revenue'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right text-xs font-bold {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                        {{ $ov['target'] > 0 ? ($diff >= 0 ? '+' : '') . number_format($diff, 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($ov['target'] > 0)
                        <span class="px-2 py-1 rounded-full text-[10px] font-black {{ $kpiColor }}">
                            {{ $ov['kpi_pct'] }}%
                        </span>
                        @else
                        <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-sm font-bold text-blue-700">{{ $ov['staff_count'] }}</td>
                    <td class="px-4 py-3 text-center text-xs text-slate-500">{{ $ov['work_days'] }} ngày</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('fe.monthly.show', ['store' => $ov['store']->id, 'month' => $month]) }}"
                            class="px-3 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 hover:bg-slate-800 hover:text-white transition-all">
                            Xem NV →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
