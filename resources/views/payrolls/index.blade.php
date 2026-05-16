@extends('layouts.app')

@section('title', 'Bảng lương tháng')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8 flex items-center justify-between">
    <form action="{{ route('fe.payrolls.index') }}" method="GET" class="flex items-center gap-6">
        <div class="w-48">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tháng lương</label>
            <input type="month" name="month" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none font-bold text-slate-700" value="{{ $month }}" onchange="this.form.submit()">
        </div>
        <div class="w-64">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cửa hàng</label>
            <select name="store_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none font-bold text-slate-700" onchange="this.form.submit()">
                <option value="">-- Chọn cửa hàng --</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->code }} - {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($storeId)
    <div class="text-right">
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">KPI Tổng Cửa hàng</p>
        <h3 class="text-xl font-bold {{ $storeKpiPercentage >= 100 ? 'text-emerald-600' : ($storeKpiPercentage >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
            {{ round($storeKpiPercentage, 1) }}%
        </h3>
    </div>
    @endif
</div>

@if($storeId && count($payrollData) > 0)
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse table-compact">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-4 py-4">Nhân viên / Chức vụ</th>
                    <th class="px-4 py-4 text-center">Công (Ngày)</th>
                    <th class="px-4 py-4 text-center">Giờ làm</th>
                    <th class="px-4 py-4 text-right">DT Cá nhân</th>
                    <th class="px-4 py-4 text-center">KPI %</th>
                    <th class="px-4 py-4 text-right">Lương cứng</th>
                    <th class="px-4 py-4 text-right">Hoa hồng (%)</th>
                    <th class="px-4 py-4 text-right">Thưởng Team</th>
                    <th class="px-4 py-4 text-right bg-slate-900 text-white">Thực lĩnh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($payrollData as $data)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-bold text-slate-800 text-sm">{{ $data['user']->full_name }}</div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">{{ $data['user']->position->name ?? 'Staff' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center font-medium">{{ $data['work_days'] }}</td>
                    <td class="px-4 py-3 text-center font-bold text-slate-600">{{ $data['total_hours'] }}h</td>
                    <td class="px-4 py-3 text-right font-mono text-xs">
                        {{ number_format($data['total_revenue'], 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $data['kpi_pct'] >= 100 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ round($data['kpi_pct'], 1) }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-sm">
                        {{ number_format($data['base_salary'], 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="font-bold text-slate-800 text-sm">{{ number_format($data['commission'], 0, ',', '.') }}đ</div>
                        <div class="text-[9px] text-slate-400">({{ $data['comm_rate'] }}%)</div>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-sm text-amber-600 font-bold">
                        {{ $data['team_bonus'] > 0 ? number_format($data['team_bonus'], 0, ',', '.') . 'đ' : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-slate-900 bg-slate-50/50">
                        {{ number_format($data['total_salary'], 0, ',', '.') }}đ
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white p-12 rounded-2xl border-2 border-dashed border-slate-200 text-center">
    <div class="text-slate-400 mb-2">Chưa có dữ liệu bảng lương cho tháng này.</div>
    <div class="text-xs text-slate-300">Vui lòng chọn Cửa hàng và Tháng để xem kết quả.</div>
</div>
@endif
@endsection
