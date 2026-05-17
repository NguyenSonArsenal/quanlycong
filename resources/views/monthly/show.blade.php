@extends('layouts.app')
@section('title', 'KPI Nhân viên — ' . $store->name . ' — ' . $month)
@section('content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('fe.monthly.index', ['month' => $month]) }}"
            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-all text-sm font-bold">←</a>
        <div>
            <h1 class="text-xl font-black text-slate-800">👥 {{ $store->name }} <span class="text-slate-400 font-medium text-base">({{ $store->code }})</span></h1>
            <p class="text-xs text-slate-400 mt-0.5">KPI nhân viên — {{ $month }}</p>
        </div>
    </div>
    <form method="GET" action="{{ route('fe.monthly.show', $store->id) }}" class="flex items-center gap-3">
        <div>
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tháng</label>
            <input type="month" name="month" value="{{ $month }}"
                class="px-3 py-1.5 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm"
                onchange="this.form.submit()">
        </div>
    </form>
    <div class="flex items-center gap-2">
        {{-- Bảng công tháng --}}
        <a href="{{ route('fe.monthly.calendar', ['store' => $store->id, 'month' => $month]) }}"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-sm">
            📋 Bảng công
        </a>
        {{-- Shortcut sang bảng lương --}}
        <a href="{{ route('fe.payrolls.index', ['month' => $month, 'store_id' => $store->id]) }}"
            class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white rounded-xl font-bold text-sm hover:bg-emerald-800 transition-all shadow-sm">
            💰 Bảng lương CH
        </a>
    </div>
</div>

{{-- Store summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Target tháng</p>
        <p class="font-black text-xl text-slate-700">{{ $storeTarget > 0 ? number_format($storeTarget/1000000, 1).'M' : '—' }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">DT thực tế</p>
        <p class="font-black text-xl text-emerald-700">{{ number_format($storeRevenue/1000000, 1) }}M</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KPI Cửa hàng</p>
        <p class="font-black text-3xl leading-none {{ $storeKpiPct >= 100 ? 'text-emerald-600' : ($storeKpiPct >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
            {{ $storeTarget > 0 ? $storeKpiPct.'%' : '—' }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Nhân sự đang làm</p>
        <p class="font-black text-xl text-blue-700">{{ count($employeeData) }}</p>
        <p class="text-[9px] text-slate-400">{{ collect($employeeData)->where('is_sales',true)->count() }} sales</p>
    </div>
</div>

{{-- Employee KPI ranking table --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-black text-slate-800 text-sm">🏆 Xếp hạng KPI cá nhân</h2>
        <span class="text-[10px] text-slate-400">Sắp xếp theo KPI% giảm dần</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-3 py-2 text-center w-16">Hạng</th>
                    <th class="px-3 py-2">Nhân viên</th>
                    <th class="px-3 py-2">Chức danh / HĐ</th>
                    <th class="px-3 py-2 text-center">Công / Giờ</th>
                    <th class="px-3 py-2 text-right">DT cá nhân</th>
                    <th class="px-3 py-2 text-right">Target</th>
                    <th class="px-3 py-2 text-right">Chênh lệch</th>
                    <th class="px-3 py-2 text-center">KPI %</th>
                    <th class="px-3 py-2 text-center">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($employeeData as $i => $ed)
                @php
                    $rank     = $i + 1;
                    $rankIcon = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : "#$rank"));
                    $kpiColor = $ed['kpi_pct'] >= 100 ? 'bg-emerald-100 text-emerald-700 font-black'
                              : ($ed['kpi_pct'] >= 90  ? 'bg-amber-100 text-amber-600 font-bold'
                              : 'bg-rose-50 text-rose-400');
                    $diff     = $ed['total_revenue'] - $ed['total_target'];
                @endphp
                <tr class="{{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }} hover:bg-blue-50/20 transition-colors">
                    <td class="px-3 py-2 text-center font-black {{ $rank <= 3 ? 'text-2xl' : 'text-slate-400 text-xs' }}">
                        {{ $rankIcon }}
                    </td>
                    <td class="px-3 py-2">
                        <div class="font-bold text-slate-800">{{ $ed['user']->full_name }}</div>
                        <div class="text-[9px] text-slate-400 font-mono">{{ $ed['user']->username }}</div>
                    </td>
                    <td class="px-3 py-2">
                        <div class="text-xs text-slate-600">{{ $ed['user']->position->name ?? '—' }}</div>
                        <span class="text-[8px] px-1.5 py-0.5 rounded font-bold {{ $ed['user']->contract_type === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $ed['user']->contract_type ?? 'CT' }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center text-xs">
                        <span class="font-black text-slate-700">{{ $ed['work_days'] }}</span>
                        <span class="text-slate-400"> ng</span>
                        <span class="text-blue-600 font-bold ml-1">{{ number_format($ed['total_hours'], 1) }}h</span>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-bold text-emerald-700">
                        {{ $ed['is_sales'] && $ed['total_revenue'] > 0 ? number_format($ed['total_revenue'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs text-slate-500">
                        {{ $ed['total_target'] > 0 ? number_format($ed['total_target'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-bold {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                        @if($ed['is_sales'] && $ed['total_target'] > 0)
                            {{ ($diff >= 0 ? '+' : '') . number_format($diff, 0, ',', '.') }}
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($ed['is_sales'])
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ $kpiColor }}">
                            {{ $ed['kpi_pct'] }}%
                        </span>
                        @else
                        <span class="text-[9px] text-slate-400 italic">non-sales</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('fe.monthly.calendar', ['store' => $store->id, 'month' => $month, 'user_id' => $ed['user']->id]) }}"
                                class="px-2 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-[10px] font-bold hover:bg-indigo-700 hover:text-white transition-all"
                                title="Xem bảng công cá nhân">
                                📋
                            </a>
                            <a href="{{ route('fe.profile', ['user_id' => $ed['user']->id, 'month' => $month]) }}"
                                class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-bold hover:bg-emerald-200 transition-all"
                                title="Xem lương">
                                💰
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center">
                        <div class="text-3xl mb-2">📭</div>
                        <p class="text-slate-400 font-medium">Chưa có dữ liệu tháng {{ $month }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
