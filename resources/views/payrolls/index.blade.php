@extends('layouts.app')

@section('title', 'Bảng lương tháng ' . $month)

@section('content')
{{-- ── Header ── --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-5 px-5 py-4 flex flex-wrap items-center gap-5">
    <form action="{{ route('fe.payrolls.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
        <div>
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tháng</label>
            <input type="month" name="month"
                class="px-3 py-1.5 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm"
                value="{{ $month }}" onchange="this.form.submit()">
        </div>
        <div class="w-52">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Cửa hàng</label>
            @if($stores->count() === 1)
                {{-- QLCH / CHP: chỉ có 1 cửa hàng, hiện tên luôn, không cần dropdown --}}
                <div class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-bold text-slate-700 text-sm">
                    {{ $stores->first()->code }} – {{ $stores->first()->name }}
                </div>
                <input type="hidden" name="store_id" value="{{ $stores->first()->id }}">
            @else
                {{-- Admin / Area Manager: dropdown Select2 --}}
                <select name="store_id" id="select-store-payroll" style="width:100%">
                    <option value="">-- Chọn --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>
                            {{ $s->code }} – {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
        @if($storeId)
        <div class="flex-1 min-w-[160px]">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tìm nhân viên</label>
            <div class="flex gap-1">
                <input type="text" name="q" value="{{ $search }}" placeholder="Tên hoặc mã NV..."
                    class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 outline-none text-sm">
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-xs font-bold hover:bg-slate-600">Tìm</button>
            </div>
        </div>
        @endif
    </form>

    @if($storeId)
    <div class="flex items-center gap-5 border-l border-slate-100 pl-5 shrink-0">
        <div class="text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase">DT thực tế</p>
            <p class="font-black text-emerald-700 text-sm">{{ number_format($storeRevenue, 0, ',', '.') }}đ</p>
        </div>
        <div class="text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase">KPI Target</p>
            <p class="font-bold text-slate-500 text-sm">{{ number_format($storeTarget, 0, ',', '.') }}đ</p>
        </div>
        <div class="text-center min-w-[90px]">
            <p class="text-[9px] font-bold text-slate-400 uppercase">KPI Cửa hàng</p>
            <p class="font-black text-3xl leading-none {{ $storeKpiPercentage >= 100 ? 'text-emerald-600' : ($storeKpiPercentage >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
                {{ number_format($storeKpiPercentage, 1) }}%
            </p>
            <p class="text-[8px] mt-0.5 font-bold
                {{ $storeKpiPercentage >= 100 ? 'text-emerald-500' : ($storeKpiPercentage >= 90 ? 'text-amber-400' : 'text-rose-400') }}">
                @if($storeKpiPercentage >= 100) ✅ 100% thưởng team
                @elseif($storeKpiPercentage >= 90) ⚡ 50% thưởng team
                @else ❌ Không thưởng team
                @endif
            </p>
        </div>
    </div>
    @endif
</div>

@if($storeId && count($payrollData) > 0)
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-[10px]" id="payroll-table">
            <thead>
                {{-- Group header --}}
                <tr class="text-[8px] uppercase font-bold tracking-wider">
                    <th colspan="5" class="px-3 py-2 bg-slate-700 text-slate-300 border-r border-slate-600 text-center">Nhân viên</th>
                    <th colspan="4" class="px-3 py-2 bg-blue-800 text-blue-200 border-r border-blue-700 text-center">Giờ công</th>
                    <th colspan="4" class="px-3 py-2 bg-emerald-800 text-emerald-200 border-r border-emerald-700 text-center">Doanh thu cá nhân</th>
                    <th colspan="2" class="px-3 py-2 bg-amber-700 text-amber-100 border-r border-amber-600 text-center">KPI %</th>
                    <th colspan="4" class="px-3 py-2 bg-purple-800 text-purple-200 border-r border-purple-700 text-center">Lương tháng</th>
                    <th colspan="1" class="px-3 py-2 bg-slate-500 text-slate-100 text-center">Giả định</th>
                    <th colspan="1" class="px-3 py-2 bg-emerald-900 text-white text-center">Thực lĩnh</th>
                    <th colspan="1" class="px-3 py-2 bg-slate-600 text-slate-100 text-center">Chi tiết</th>
                </tr>
                {{-- Column header --}}
                <tr class="bg-slate-800 text-white text-[9px] font-bold uppercase tracking-wider">
                    {{-- Nhân viên --}}
                    <th class="px-3 py-2 min-w-[140px]">Họ tên</th>
                    <th class="px-3 py-2 min-w-[70px]">Mã NV</th>
                    <th class="px-3 py-2 min-w-[120px]">Chức danh</th>
                    <th class="px-3 py-2 text-center">HĐ</th>
                    <th class="px-3 py-2 text-right border-r border-slate-600">Lương/h</th>
                    {{-- Giờ công --}}
                    <th class="px-2 py-2 text-center bg-blue-900">☀ Sáng</th>
                    <th class="px-2 py-2 text-center bg-blue-900">🌤 Chiều</th>
                    <th class="px-2 py-2 text-center bg-blue-900">🌙 Tối</th>
                    <th class="px-2 py-2 text-center bg-blue-900 border-r border-blue-700">Tổng (h)</th>
                    {{-- DT --}}
                    <th class="px-2 py-2 text-right bg-emerald-900">Sáng</th>
                    <th class="px-2 py-2 text-right bg-emerald-900">Chiều</th>
                    <th class="px-2 py-2 text-right bg-emerald-900">Tối</th>
                    <th class="px-2 py-2 text-right bg-emerald-900 border-r border-emerald-700">Tổng DT</th>
                    {{-- KPI --}}
                    <th class="px-2 py-2 text-center bg-amber-800">Cá nhân</th>
                    <th class="px-2 py-2 text-center bg-amber-800 border-r border-amber-700">Cửa hàng</th>
                    {{-- Lương --}}
                    <th class="px-2 py-2 text-center bg-purple-900">Rate%</th>
                    <th class="px-2 py-2 text-right bg-purple-900">HH (DT×rate)</th>
                    <th class="px-2 py-2 text-right bg-purple-900">Lương cứng</th>
                    <th class="px-2 py-2 text-right bg-purple-900 border-r border-purple-700">Thưởng Team</th>
                    {{-- Giả định 95% --}}
                    <th class="px-2 py-2 text-right bg-slate-600 border-r border-slate-500">GĐ 95% KPI</th>
                    {{-- Thực lĩnh --}}
                    <th class="px-2 py-2 text-right bg-emerald-800 min-w-[120px]">Thực lĩnh</th>
                    {{-- Chi tiết --}}
                    <th class="px-2 py-2 text-center bg-slate-700 min-w-[80px]">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($payrollData as $d)
                @php
                    $kpiColor = $d['personal_kpi'] >= 100 ? 'bg-emerald-100 text-emerald-700'
                              : ($d['personal_kpi'] >= 90 ? 'bg-amber-100 text-amber-700'
                              : 'bg-slate-100 text-slate-500');
                @endphp
                <tr id="user-{{ $d['user']->id }}" class="{{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }} hover:bg-blue-50/20 transition-colors">
                    {{-- Nhân viên --}}
                    <td class="px-3 py-2.5">
                        <div class="font-bold text-slate-800">{{ $d['user']->full_name }}</div>
                    </td>
                    <td class="px-3 py-2.5 text-slate-500 font-mono text-[9px]">{{ $d['user']->username }}</td>
                    <td class="px-3 py-2.5 text-slate-600">{{ $d['user']->position->name ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="px-1.5 py-0.5 rounded text-[8px] font-bold {{ $d['user']->contract_type === 'CT' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ $d['user']->contract_type ?? 'CT' }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-right border-r border-slate-100 font-mono">
                        {{ $d['hourly_rate'] > 0 ? number_format($d['hourly_rate'], 0, ',', '.') : '—' }}
                    </td>

                    {{-- Giờ công từng ca --}}
                    <td class="px-2 py-2.5 text-center bg-blue-50/30 font-bold {{ $d['shift_hours']['morning'] > 0 ? 'text-blue-700' : 'text-slate-300' }}">
                        {{ $d['shift_hours']['morning'] > 0 ? number_format($d['shift_hours']['morning'], 1) : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-center bg-blue-50/30 font-bold {{ $d['shift_hours']['afternoon'] > 0 ? 'text-blue-700' : 'text-slate-300' }}">
                        {{ $d['shift_hours']['afternoon'] > 0 ? number_format($d['shift_hours']['afternoon'], 1) : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-center bg-blue-50/30 font-bold {{ $d['shift_hours']['evening'] > 0 ? 'text-blue-700' : 'text-slate-300' }}">
                        {{ $d['shift_hours']['evening'] > 0 ? number_format($d['shift_hours']['evening'], 1) : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-center bg-blue-50/30 border-r border-blue-100">
                        <span class="font-black text-blue-800">{{ number_format($d['total_hours'], 1) }}</span>
                        <span class="text-slate-400 ml-0.5">h</span>
                    </td>

                    {{-- DT từng ca --}}
                    <td class="px-2 py-2.5 text-right bg-emerald-50/20 text-[9px] {{ $d['shift_revenue']['morning'] > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $d['shift_revenue']['morning'] > 0 ? number_format($d['shift_revenue']['morning'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-right bg-emerald-50/20 text-[9px] {{ $d['shift_revenue']['afternoon'] > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $d['shift_revenue']['afternoon'] > 0 ? number_format($d['shift_revenue']['afternoon'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-right bg-emerald-50/20 text-[9px] {{ $d['shift_revenue']['evening'] > 0 ? 'text-emerald-700 font-bold' : 'text-slate-300' }}">
                        {{ $d['shift_revenue']['evening'] > 0 ? number_format($d['shift_revenue']['evening'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-2 py-2.5 text-right bg-emerald-50/20 border-r border-emerald-100">
                        @if($d['is_sales'])
                        <div class="font-black text-emerald-800 text-xs">{{ number_format($d['total_revenue'], 0, ',', '.') }}</div>
                        @if($d['total_target'] > 0)
                        <div class="text-[8px] text-slate-400">T: {{ number_format($d['total_target'], 0, ',', '.') }}</div>
                        @endif
                        @else<span class="text-slate-300">—</span>@endif
                    </td>

                    {{-- KPI % --}}
                    <td class="px-2 py-2.5 text-center bg-amber-50/30">
                        @if($d['is_sales'])
                        <span class="px-2 py-0.5 rounded-full font-black {{ $kpiColor }}">{{ $d['personal_kpi'] }}%</span>
                        @else<span class="text-slate-300">—</span>@endif
                    </td>
                    <td class="px-2 py-2.5 text-center bg-amber-50/30 border-r border-amber-100">
                        <span class="font-bold {{ $d['store_kpi'] >= 100 ? 'text-emerald-600' : ($d['store_kpi'] >= 90 ? 'text-amber-500' : 'text-rose-500') }}">
                            {{ $d['store_kpi'] }}%
                        </span>
                    </td>

                    {{-- Lương --}}
                    <td class="px-2 py-2.5 text-center bg-purple-50/20">
                        @if($d['is_sales'] && $d['comm_rate'] > 0)
                        <span class="font-black text-purple-700">{{ $d['comm_rate'] }}%</span>
                        @else<span class="text-slate-300">—</span>@endif
                    </td>
                    <td class="px-2 py-2.5 text-right bg-purple-50/20">
                        @if($d['commission'] > 0)
                        <span class="font-bold text-purple-700">{{ number_format($d['commission'], 0, ',', '.') }}</span>
                        @else<span class="text-slate-300">—</span>@endif
                    </td>
                    <td class="px-2 py-2.5 text-right bg-purple-50/20">
                        @if($d['base_salary'] > 0)
                        <div class="font-bold text-slate-800">{{ number_format($d['base_salary'], 0, ',', '.') }}</div>
                        <div class="text-[8px] text-slate-400">{{ number_format($d['total_hours'], 1) }}h × {{ number_format($d['hourly_rate'], 0, ',', '.') }}</div>
                        @else<span class="text-slate-300">0</span>@endif
                    </td>
                    <td class="px-2 py-2.5 text-right bg-purple-50/20 border-r border-purple-100">
                        @if($d['team_bonus'] > 0)
                        <span class="font-black text-amber-600">{{ number_format($d['team_bonus'], 0, ',', '.') }}</span>
                        @else<span class="text-slate-300">—</span>@endif
                    </td>

                    {{-- Giả định 95% KPI --}}
                    <td class="px-2 py-2.5 text-right border-r border-slate-200 bg-slate-50/60">
                        @if($d['is_sales'])
                        <div class="font-bold text-slate-600 text-[9px]">{{ number_format($d['total_hypo'], 0, ',', '.') }}</div>
                        <div class="text-[8px] text-slate-400">({{ $d['comm_rate_hypo'] }}%)</div>
                        @else<span class="text-slate-300">—</span>@endif
                    </td>

                    {{-- Thực lĩnh --}}
                    <td class="px-3 py-2.5 text-right bg-emerald-50/50">
                        <div class="font-black text-emerald-900 text-sm">{{ number_format($d['total_salary'], 0, ',', '.') }}đ</div>
                    </td>
                    {{-- Chi tiết --}}
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ route('fe.monthly.index', ['store_id' => $d['user']->store_id, 'user_id' => $d['user']->id, 'month' => $month]) }}"
                            class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-[9px] font-bold hover:bg-indigo-700 hover:text-white transition-all whitespace-nowrap">
                            📋 Chi tiết
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            {{-- Footer --}}
            <tfoot class="bg-slate-900 text-white text-[9px] font-bold">
                <tr>
                    <td class="px-3 py-2 uppercase tracking-wider" colspan="4">Tổng cộng</td>
                    <td class="px-3 py-2 border-r border-slate-600"></td>
                    <td colspan="3" class="px-2 py-2 text-center bg-blue-950">—</td>
                    <td class="px-2 py-2 text-center bg-blue-950 border-r border-blue-800">
                        {{ number_format(collect($payrollData)->sum('total_hours'), 1) }}h
                    </td>
                    <td colspan="3" class="px-2 py-2 bg-emerald-950 text-emerald-300">—</td>
                    <td class="px-2 py-2 text-right bg-emerald-950 border-r border-emerald-800 text-emerald-300">
                        {{ number_format(collect($payrollData)->sum('total_revenue'), 0, ',', '.') }}đ
                    </td>
                    <td colspan="2" class="px-2 py-2 border-r border-amber-900 bg-amber-950">—</td>
                    <td class="px-2 py-2 text-center bg-purple-950">—</td>
                    <td class="px-2 py-2 text-right bg-purple-950 text-purple-300">
                        {{ number_format(collect($payrollData)->sum('commission'), 0, ',', '.') }}đ
                    </td>
                    <td class="px-2 py-2 text-right bg-purple-950">
                        {{ number_format(collect($payrollData)->sum('base_salary'), 0, ',', '.') }}đ
                    </td>
                    <td class="px-2 py-2 text-right bg-purple-950 border-r border-purple-800 text-amber-300">
                        {{ number_format(collect($payrollData)->sum('team_bonus'), 0, ',', '.') }}đ
                    </td>
                    <td class="px-2 py-2 border-r border-slate-700"></td>
                    <td class="px-3 py-2 text-right bg-emerald-800 text-emerald-200 text-sm">
                        {{ number_format(collect($payrollData)->sum('total_salary'), 0, ',', '.') }}đ
                    </td>
                    <td class="px-3 py-2 bg-slate-700"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@elseif($storeId)
<div class="bg-white p-12 rounded-2xl border-2 border-dashed border-slate-200 text-center">
    <div class="text-3xl mb-3">📋</div>
    <p class="font-bold text-slate-500">Chưa có dữ liệu tháng {{ $month }}</p>
</div>
@else
<div class="bg-white p-12 rounded-2xl border-2 border-dashed border-slate-200 text-center">
    <div class="text-3xl mb-3">🏪</div>
    <p class="font-bold text-slate-500">Chọn cửa hàng và tháng để xem bảng lương</p>
</div>
@endif

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if($stores->count() > 1)
    // Select2 cho dropdown cửa hàng (Admin / Area Manager)
    $('#select-store-payroll').select2({
        placeholder: '-- Chọn cửa hàng --',
        allowClear: false,
        width: '100%',
        language: {
            searching: function() { return 'Đang tìm...'; },
            noResults: function() { return 'Không tìm thấy'; }
        }
    }).on('select2:select', function () {
        $(this).closest('form').submit();
    });
    @endif

    @if($stores->count() === 1 && !$storeId)
    // Auto-submit khi QLCH/CHP vào trang lần đầu chưa có store_id trong URL
    document.querySelector('form').submit();
    @endif
});
</script>
@endpush
