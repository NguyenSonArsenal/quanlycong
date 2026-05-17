@extends('layouts.app')
@section('title', 'Bảng doanh thu — ' . $store->name . ' — ' . $month)
@section('content')

{{-- ── Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('fe.monthly.index', ['month' => $month]) }}"
            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-all text-sm font-bold">←</a>
        <div>
            <h1 class="text-xl font-black text-slate-800">📈 {{ $store->name }}
                <span class="text-slate-400 font-medium text-base">({{ $store->code }})</span>
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Cal_Bảng doanh thu — {{ $month }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('fe.monthly.revenue', $store->id) }}" class="flex items-center gap-2">
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tháng</label>
                <input type="month" name="month" value="{{ $month }}"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm"
                    onchange="this.form.submit()">
            </div>
        </form>
        <a href="{{ route('fe.monthly.show', ['store' => $store->id, 'month' => $month]) }}"
            class="flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-all shadow-sm">
            👥 Xem NV
        </a>
    </div>
</div>

{{-- ── Summary Cards ── --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4 md:col-span-2">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng DT tháng</p>
        <p class="font-black text-2xl text-emerald-700">{{ number_format($grandTotalDT / 1000000, 1) }}M</p>
        @if($storeTarget > 0)
        <div class="mt-2">
            <div class="flex justify-between text-[9px] text-slate-400 mb-1">
                <span>Target: {{ number_format($storeTarget/1000000,1) }}M</span>
                <span class="font-bold {{ $kpiPct >= 100 ? 'text-emerald-600' : ($kpiPct >= 90 ? 'text-amber-500' : 'text-rose-500') }}">{{ $kpiPct }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5">
                <div class="h-1.5 rounded-full transition-all {{ $kpiPct >= 100 ? 'bg-emerald-500' : ($kpiPct >= 90 ? 'bg-amber-400' : 'bg-rose-400') }}"
                    style="width: {{ min(100, $kpiPct) }}%"></div>
            </div>
        </div>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Ngày có data</p>
        <p class="font-black text-xl text-blue-700">{{ count($dailyRows) }}</p>
        <p class="text-[9px] text-slate-400">ngày trong tháng</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng công (giờ)</p>
        <p class="font-black text-xl text-indigo-700">{{ number_format($grandTotalHours, 1) }}</p>
        <p class="text-[9px] text-slate-400">giờ làm việc</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KH / HĐ / SP</p>
        <p class="font-black text-lg text-slate-700">{{ number_format($grandSlkh) }}</p>
        <p class="text-[9px] text-slate-400">{{ number_format($grandSlhd) }} HĐ · {{ number_format($grandSlsp) }} SP</p>
    </div>
</div>

{{-- ── Daily Revenue Table ── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-black text-slate-800 text-sm">📅 Doanh thu theo ngày — {{ $month }}</h2>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3 text-[9px] text-slate-400">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-1.5 rounded-full bg-emerald-400"></span> Ngày DT cao nhất</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-1.5 rounded-full bg-slate-200"></span> Tuần chẵn (Xám)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-1.5 rounded-full bg-white border border-slate-200"></span> Tuần lẻ (Trắng)</span>
            </div>
            <span class="text-[10px] text-slate-400">{{ count($dailyRows) }} ngày có dữ liệu</span>
        </div>
    </div>

    @if(count($dailyRows) === 0)
    <div class="px-5 py-16 text-center">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-slate-400 font-medium">Chưa có dữ liệu doanh thu tháng {{ $month }}</p>
        <p class="text-xs text-slate-300 mt-1">Nhân viên cần nhập ca làm tại Bảng công ngày</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table id="revenue-table" class="w-full text-left border-collapse text-sm min-w-[900px]">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-3 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3">Ngày</th>
                    <th class="px-3 py-3 text-center">T.</th>
                    <th class="px-3 py-3 text-center">Tuần</th>
                    <th class="px-3 py-3 text-center">SLKH</th>
                    <th class="px-3 py-3 text-center">SLHD</th>
                    <th class="px-3 py-3 text-center">SLSP</th>
                    <th class="px-4 py-3 text-right bg-blue-900/40">Ca Sáng</th>
                    <th class="px-4 py-3 text-right bg-amber-900/30">Ca Chiều</th>
                    <th class="px-4 py-3 text-right bg-slate-700/50">Ca Tối</th>
                    <th id="th-dt" class="px-4 py-3 text-right cursor-pointer select-none hover:bg-slate-700 transition-colors"
                        onclick="sortByDT()" title="Click để sắp xếp">
                        Tổng DT
                        <span id="sort-icon" class="ml-1 text-slate-400">⇅</span>
                    </th>
                    <th class="px-4 py-3 text-center">Công (giờ)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $rowNum = 0; @endphp
                @foreach($dailyRows as $row)
                @php
                    $rowNum++;
                    $isWeekend   = in_array($row['day_of_week'], ['T7', 'CN']);
                    $pct         = $maxDayDT > 0 ? min(100, round($row['total_dt'] / $maxDayDT * 100)) : 0;
                    $isMaxDay    = $maxDayDT > 0 && $row['total_dt'] >= $maxDayDT;
                    // Xen kẽ theo tuần chẵn lẻ: Tuần chẵn màu bg-slate-100 rõ nét, tuần lẻ màu bg-white
                    $rowBg       = $isMaxDay ? 'bg-emerald-100' : (($row['week_num'] ?? 0) % 2 === 0 ? 'bg-slate-100' : 'bg-white');
                @endphp
                <tr class="{{ $rowBg }} hover:bg-blue-50/30 transition-colors group" data-dt="{{ $row['total_dt'] }}">
                    {{-- # --}}
                    <td class="px-3 py-2.5 text-center text-[10px] text-slate-400 font-mono">{{ $rowNum }}</td>

                    {{-- Ngày --}}
                    <td class="px-4 py-2.5">
                        <div class="font-bold text-slate-800 text-xs">{{ $row['date_fmt'] }}</div>
                    </td>

                    {{-- Thứ --}}
                    <td class="px-3 py-2.5 text-center">
                        <span class="text-[10px] font-black {{ $isWeekend ? 'text-amber-600' : 'text-slate-500' }}">
                            {{ $row['day_of_week'] }}
                        </span>
                    </td>

                    {{-- Tuần --}}
                    <td class="px-3 py-2.5 text-center">
                        <span class="text-[10px] text-slate-400">{{ $row['week_label'] }}</span>
                    </td>

                    {{-- SLKH --}}
                    <td class="px-3 py-2.5 text-center">
                        <span class="font-bold text-xs text-slate-700">{{ $row['slkh'] > 0 ? $row['slkh'] : '—' }}</span>
                    </td>

                    {{-- SLHD --}}
                    <td class="px-3 py-2.5 text-center">
                        <span class="font-bold text-xs text-slate-700">{{ $row['slhd'] > 0 ? $row['slhd'] : '—' }}</span>
                    </td>

                    {{-- SLSP --}}
                    <td class="px-3 py-2.5 text-center">
                        <span class="font-bold text-xs text-slate-700">{{ $row['slsp'] > 0 ? $row['slsp'] : '—' }}</span>
                    </td>

                    {{-- Ca Sáng --}}
                    <td class="px-4 py-2.5 text-right bg-blue-50/20">
                        <span class="text-xs font-mono {{ $row['morning'] > 0 ? 'text-blue-700 font-bold' : 'text-slate-300' }}">
                            {{ $row['morning'] > 0 ? number_format($row['morning'], 0, ',', '.') : '—' }}
                        </span>
                    </td>

                    {{-- Ca Chiều --}}
                    <td class="px-4 py-2.5 text-right bg-amber-50/20">
                        <span class="text-xs font-mono {{ $row['afternoon'] > 0 ? 'text-amber-700 font-bold' : 'text-slate-300' }}">
                            {{ $row['afternoon'] > 0 ? number_format($row['afternoon'], 0, ',', '.') : '—' }}
                        </span>
                    </td>

                    {{-- Ca Tối --}}
                    <td class="px-4 py-2.5 text-right bg-slate-50/50">
                        <span class="text-xs font-mono {{ $row['evening'] > 0 ? 'text-slate-700 font-bold' : 'text-slate-300' }}">
                            {{ $row['evening'] > 0 ? number_format($row['evening'], 0, ',', '.') : '—' }}
                        </span>
                    </td>

                    {{-- Tổng DT + mini bar --}}
                    <td class="px-4 py-2.5 text-right">
                        <div class="font-black text-xs {{ $isMaxDay ? 'text-emerald-700' : 'text-slate-800' }}">
                            {{ number_format($row['total_dt'], 0, ',', '.') }}
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1 mt-1">
                            <div class="h-1 rounded-full {{ $isMaxDay ? 'bg-emerald-500' : 'bg-indigo-400' }} transition-all"
                                style="width: {{ $pct }}%"></div>
                        </div>
                    </td>

                    {{-- Tổng công --}}
                    <td class="px-4 py-2.5 text-center">
                        <span class="text-xs font-bold text-indigo-700">{{ number_format($row['total_hours'], 1) }}</span>
                        <span class="text-[9px] text-slate-400">h</span>
                    </td>
                </tr>
                @endforeach
            </tbody>

            {{-- ── Footer Tổng ── --}}
            <tfoot class="bg-slate-800 text-white text-xs font-bold border-t-2 border-slate-600">
                <tr>
                    <td class="px-3 py-3 text-center text-slate-400" colspan="2">TỔNG THÁNG</td>
                    <td class="px-3 py-3 text-center text-slate-400" colspan="2">{{ count($dailyRows) }} ngày</td>
                    <td class="px-3 py-3 text-center text-amber-300">{{ number_format($grandSlkh) }}</td>
                    <td class="px-3 py-3 text-center text-amber-300">{{ number_format($grandSlhd) }}</td>
                    <td class="px-3 py-3 text-center text-amber-300">{{ number_format($grandSlsp) }}</td>
                    <td class="px-4 py-3 text-right text-blue-300">{{ number_format(array_sum(array_column($dailyRows, 'morning')), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-amber-300">{{ number_format(array_sum(array_column($dailyRows, 'afternoon')), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-slate-300">{{ number_format(array_sum(array_column($dailyRows, 'evening')), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-emerald-400 text-sm">{{ number_format($grandTotalDT, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center text-indigo-300">{{ number_format($grandTotalHours, 1) }}h</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
let sortDir = null; // null = default, 'desc' = cao→thấp, 'asc' = thấp→cao

function sortByDT() {
    // Toggle direction
    if (sortDir === null || sortDir === 'asc') {
        sortDir = 'desc';
    } else {
        sortDir = 'asc';
    }

    const icon = document.getElementById('sort-icon');
    icon.textContent = sortDir === 'desc' ? '↓' : '↑';
    icon.className = 'ml-1 ' + (sortDir === 'desc' ? 'text-emerald-400' : 'text-amber-400');

    const tbody = document.querySelector('#revenue-table tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        // data-dt attribute được gắn sẵn vào mỗi row
        const valA = parseFloat(a.dataset.dt || 0);
        const valB = parseFloat(b.dataset.dt || 0);
        return sortDir === 'desc' ? valB - valA : valA - valB;
    });

    rows.forEach(r => tbody.appendChild(r));

    // Cập nhật lại số thứ tự (#)
    rows.forEach((r, i) => {
        const numCell = r.querySelector('td:first-child');
        if (numCell) numCell.textContent = i + 1;
    });
}
</script>
@endpush
