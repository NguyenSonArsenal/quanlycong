@extends('layouts.app')
@section('title', ($selectedUser ? $selectedUser->full_name . ' — ' : '') . 'Bảng công — ' . $store->name . ' — ' . $month)
@section('content')

{{-- ── Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('fe.monthly.show', ['store' => $store->id, 'month' => $month]) }}"
            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-all text-sm font-bold">←</a>
        <div>
            @if($selectedUser)
            <h1 class="text-xl font-black text-slate-800">
                📋 {{ $selectedUser->full_name }}
                <span class="text-slate-400 font-medium text-sm ml-1">#{{ $selectedUser->username }}</span>
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">
                Bảng công cá nhân — {{ $store->name }} ({{ $store->code }}) — {{ $month }}
            </p>
            @else
            <h1 class="text-xl font-black text-slate-800">📋 Cal_Bảng công
                <span class="text-slate-400 font-medium text-base"> — {{ $store->name }} ({{ $store->code }})</span>
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Toàn bộ nhân viên × {{ $month }}</p>
            @endif
        </div>
    </div>

    {{-- Controls --}}
    <div class="flex items-center gap-3 flex-wrap">
        {{-- Filter NV --}}
        <form method="GET" action="{{ route('fe.monthly.calendar', $store->id) }}" class="flex items-center gap-2">
            <input type="hidden" name="month" value="{{ $month }}">
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Lọc nhân viên</label>
                <select name="user_id" id="select-user"
                    class="min-w-[200px]" style="width: 200px">
                    <option value="">— Tất cả nhân viên —</option>
                    @foreach($allUsers as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                        {{ $u->full_name }} ({{ $u->username }})
                    </option>
                    @endforeach
                </select>
            </div>
            {{-- Chọn tháng --}}
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tháng</label>
                <input type="month" name="month" value="{{ $month }}"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 outline-none font-bold text-slate-700 text-sm"
                    onchange="this.form.submit()">
            </div>
        </form>

        {{-- Shortcut links --}}
        <div class="flex flex-col gap-1 pt-4">
            <a href="{{ route('fe.monthly.revenue', ['store' => $store->id, 'month' => $month]) }}"
                class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-700 hover:text-white transition-all">
                📈 Bảng DT
            </a>
        </div>
    </div>
</div>

{{-- ── Summary Cards ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng DT {{ $selectedUser ? 'cá nhân' : 'store' }}</p>
        <p class="font-black text-xl text-emerald-700">{{ $grandTotalDT > 0 ? number_format($grandTotalDT/1000000,1).'M' : '—' }}</p>
        @if($storeTarget > 0 && !$selectedUser)
        <p class="text-[9px] text-slate-400 mt-0.5">KPI: <span class="font-bold {{ $kpiPctStore >= 100 ? 'text-emerald-600' : ($kpiPctStore >= 90 ? 'text-amber-500' : 'text-rose-500') }}">{{ $kpiPctStore }}%</span></p>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng giờ làm</p>
        <p class="font-black text-xl text-indigo-700">{{ number_format($grandTotalHours, 1) }}h</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Số rows dữ liệu</p>
        <p class="font-black text-xl text-slate-700">{{ count($rows) }}</p>
        <p class="text-[9px] text-slate-400">ca làm trong tháng</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        @if($selectedUser)
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Chức danh</p>
        <p class="font-black text-sm text-slate-700">{{ $selectedUser->position?->name ?? '—' }}</p>
        <span class="text-[9px] px-2 py-0.5 rounded font-bold {{ $selectedUser->contract_type === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
            {{ $selectedUser->contract_type ?? 'CT' }}
        </span>
        @else
        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Số NV có data</p>
        <p class="font-black text-xl text-blue-700">{{ collect($rows)->pluck('user.id')->unique()->count() }}</p>
        <p class="text-[9px] text-slate-400">/ {{ $allUsers->count() }} nhân sự</p>
        @endif
    </div>
</div>

{{-- ── Main Table ── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-black text-slate-800 text-sm">
            @if($selectedUser)
            📋 Bảng công — {{ $selectedUser->full_name }}
            @else
            📋 Bảng công toàn bộ nhân viên — {{ $month }}
            @endif
        </h2>
        <div class="flex items-center gap-3">
            <span class="text-[10px] text-slate-400">{{ count($rows) }} dòng dữ liệu</span>
            @if($userId)
            <a href="{{ route('fe.monthly.calendar', ['store' => $store->id, 'month' => $month]) }}"
                class="text-[10px] font-bold text-rose-500 hover:text-rose-700 transition-colors">✕ Bỏ lọc</a>
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
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm" style="min-width: 1100px">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider sticky top-0 z-10">
                <tr>
                    <th class="px-3 py-3 text-center w-8">#</th>
                    <th class="px-3 py-3 w-24">Ngày</th>
                    <th class="px-2 py-3 text-center w-8">T.</th>
                    @if(!$selectedUser)
                    <th class="px-4 py-3 min-w-[140px]">Họ và tên</th>
                    <th class="px-3 py-3 text-center">ID</th>
                    <th class="px-3 py-3 text-center">Chức danh</th>
                    <th class="px-2 py-3 text-center w-10">HĐ</th>
                    @endif
                    {{-- Giờ công --}}
                    <th class="px-3 py-3 text-center bg-blue-900/30">GC Sáng</th>
                    <th class="px-3 py-3 text-center bg-amber-900/20">GC Chiều</th>
                    <th class="px-3 py-3 text-center bg-slate-700/40">GC Tối</th>
                    <th class="px-3 py-3 text-center bg-purple-900/20">GC BS</th>
                    {{-- Doanh thu cá nhân --}}
                    <th class="px-4 py-3 text-right bg-blue-900/20">DTCN Sáng</th>
                    <th class="px-4 py-3 text-right bg-amber-900/15">DTCN Chiều</th>
                    <th class="px-4 py-3 text-right bg-slate-700/30">DTCN Tối</th>
                    {{-- Số liệu phụ --}}
                    <th class="px-2 py-3 text-center">KH</th>
                    <th class="px-2 py-3 text-center">Thử đồ</th>
                    <th class="px-2 py-3 text-center">Đơn</th>
                    <th class="px-2 py-3 text-center">SP</th>
                    {{-- KPI + Tổng --}}
                    <th class="px-3 py-3 text-center">KPI%</th>
                    <th class="px-3 py-3 text-right">Tổng giờ</th>
                    <th class="px-4 py-3 text-right">Tổng DTCN</th>
                    <th class="px-3 py-3 text-center">Tháng</th>
                    <th class="px-3 py-3 text-center">Tuần</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $prevDate = null; $rowNum = 0; @endphp
                @foreach($rows as $row)
                @php
                    $rowNum++;
                    $isWeekend   = in_array($row['day_of_week'], ['T7', 'CN']);
                    $isNewDate   = $prevDate !== $row['date'];
                    $prevDate    = $row['date'];
                    $kpiColor    = $row['kpi_pct'] >= 100 ? 'text-emerald-600 font-black'
                                 : ($row['kpi_pct'] >= 90 ? 'text-amber-600 font-bold'
                                 : ($row['kpi_pct'] > 0 ? 'text-rose-500' : 'text-slate-300'));
                    $rowBg       = $isWeekend ? 'bg-amber-50/50' : ($loop->even ? 'bg-slate-50/30' : 'bg-white');
                    $isSales     = $row['user']->position?->is_sales ?? false;
                @endphp
                <tr class="{{ $rowBg }} hover:bg-blue-50/40 transition-colors {{ $isNewDate && !$loop->first ? 'border-t-2 border-slate-200' : '' }}">
                    {{-- # --}}
                    <td class="px-3 py-2 text-center text-[10px] text-slate-300 font-mono">{{ $rowNum }}</td>

                    {{-- Ngày + Thứ --}}
                    <td class="px-3 py-2">
                        <div class="font-bold text-slate-800 text-xs">{{ $row['date_fmt'] }}</div>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <span class="text-[10px] font-black {{ $isWeekend ? 'text-amber-600' : 'text-slate-400' }}">
                            {{ $row['day_of_week'] }}
                        </span>
                    </td>

                    @if(!$selectedUser)
                    {{-- Họ và tên - clickable để filter --}}
                    <td class="px-4 py-2">
                        <a href="{{ route('fe.monthly.calendar', ['store' => $store->id, 'month' => $month, 'user_id' => $row['user']->id]) }}"
                            class="font-bold text-slate-800 text-xs hover:text-indigo-700 transition-colors hover:underline">
                            {{ $row['user']->full_name }}
                        </a>
                    </td>
                    {{-- ID --}}
                    <td class="px-3 py-2 text-center">
                        <span class="text-[9px] font-mono text-slate-400">{{ $row['user']->username }}</span>
                    </td>
                    {{-- Chức danh --}}
                    <td class="px-3 py-2 text-center">
                        <span class="text-[9px] text-slate-600">{{ $row['user']->position?->name ?? '—' }}</span>
                    </td>
                    {{-- Loại HĐ --}}
                    <td class="px-2 py-2 text-center">
                        <span class="text-[8px] px-1.5 py-0.5 rounded font-bold {{ $row['user']->contract_type === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $row['user']->contract_type ?? 'CT' }}
                        </span>
                    </td>
                    @endif

                    {{-- Giờ công theo ca --}}
                    <td class="px-3 py-2 text-center bg-blue-50/20">
                        <span class="text-xs {{ $row['gc_sang'] > 0 ? 'font-bold text-blue-700' : 'text-slate-200' }}">
                            {{ $row['gc_sang'] > 0 ? number_format($row['gc_sang'], 1) : '—' }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center bg-amber-50/15">
                        <span class="text-xs {{ $row['gc_chieu'] > 0 ? 'font-bold text-amber-700' : 'text-slate-200' }}">
                            {{ $row['gc_chieu'] > 0 ? number_format($row['gc_chieu'], 1) : '—' }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center bg-slate-50/50">
                        <span class="text-xs {{ $row['gc_toi'] > 0 ? 'font-bold text-slate-700' : 'text-slate-200' }}">
                            {{ $row['gc_toi'] > 0 ? number_format($row['gc_toi'], 1) : '—' }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center bg-purple-50/20">
                        <span class="text-xs {{ $row['gc_bs'] > 0 ? 'font-bold text-purple-700' : 'text-slate-200' }}">
                            {{ $row['gc_bs'] > 0 ? number_format($row['gc_bs'], 1) : '—' }}
                        </span>
                    </td>

                    {{-- DT cá nhân theo ca --}}
                    <td class="px-4 py-2 text-right bg-blue-50/10">
                        <span class="text-xs font-mono {{ $row['dt_sang'] > 0 ? 'font-bold text-blue-700' : 'text-slate-200' }}">
                            {{ $row['dt_sang'] > 0 ? number_format($row['dt_sang'], 0, ',', '.') : '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right bg-amber-50/10">
                        <span class="text-xs font-mono {{ $row['dt_chieu'] > 0 ? 'font-bold text-amber-700' : 'text-slate-200' }}">
                            {{ $row['dt_chieu'] > 0 ? number_format($row['dt_chieu'], 0, ',', '.') : '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right bg-slate-50/50">
                        <span class="text-xs font-mono {{ $row['dt_toi'] > 0 ? 'font-bold text-slate-700' : 'text-slate-200' }}">
                            {{ $row['dt_toi'] > 0 ? number_format($row['dt_toi'], 0, ',', '.') : '—' }}
                        </span>
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
                        <span class="text-xs font-black text-indigo-700">{{ number_format($row['total_hours'], 1) }}</span>
                        <span class="text-[9px] text-slate-400">h</span>
                    </td>

                    {{-- Tổng DTCN --}}
                    <td class="px-4 py-2 text-right">
                        @if($row['total_dt'] > 0)
                        <span class="text-xs font-black text-emerald-700">{{ number_format($row['total_dt'], 0, ',', '.') }}</span>
                        @else
                        <span class="text-slate-200 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Tháng --}}
                    <td class="px-3 py-2 text-center text-[10px] text-slate-400">{{ $row['month_fmt'] }}</td>

                    {{-- Tuần --}}
                    <td class="px-3 py-2 text-center text-[10px] text-slate-400">{{ $row['week_label'] }}</td>
                </tr>
                @endforeach
            </tbody>

            {{-- ── Footer tổng ── --}}
            <tfoot class="bg-slate-800 text-white text-xs font-bold border-t-2 border-slate-600">
                <tr>
                    <td class="px-3 py-3 text-center text-slate-400" colspan="{{ $selectedUser ? 2 : 7 }}">TỔNG THÁNG</td>
                    {{-- Tổng giờ công theo ca --}}
                    <td class="px-3 py-3 text-center text-blue-300">{{ number_format(array_sum(array_column($rows, 'gc_sang')), 1) }}</td>
                    <td class="px-3 py-3 text-center text-amber-300">{{ number_format(array_sum(array_column($rows, 'gc_chieu')), 1) }}</td>
                    <td class="px-3 py-3 text-center text-slate-300">{{ number_format(array_sum(array_column($rows, 'gc_toi')), 1) }}</td>
                    <td class="px-3 py-3 text-center text-purple-300">{{ number_format(array_sum(array_column($rows, 'gc_bs')), 1) }}</td>
                    {{-- Tổng DT theo ca --}}
                    <td class="px-4 py-3 text-right text-blue-300">{{ number_format(array_sum(array_column($rows, 'dt_sang')), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-amber-300">{{ number_format(array_sum(array_column($rows, 'dt_chieu')), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-slate-300">{{ number_format(array_sum(array_column($rows, 'dt_toi')), 0, ',', '.') }}</td>
                    {{-- Số liệu phụ --}}
                    <td class="px-2 py-3 text-center text-amber-300">{{ number_format(array_sum(array_column($rows, 'so_kh'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows, 'thu_do'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows, 'so_don'))) }}</td>
                    <td class="px-2 py-3 text-center text-slate-400">{{ number_format(array_sum(array_column($rows, 'so_sp'))) }}</td>
                    <td class="px-3 py-3 text-center text-slate-400">—</td>
                    {{-- Tổng giờ --}}
                    <td class="px-3 py-3 text-right text-indigo-300">{{ number_format($grandTotalHours, 1) }}h</td>
                    {{-- Tổng DT --}}
                    <td class="px-4 py-3 text-right text-emerald-400 text-sm">{{ number_format($grandTotalDT, 0, ',', '.') }}</td>
                    <td class="px-3 py-3 text-center text-slate-400" colspan="2">— {{ $month }} —</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#select-user').select2({
        placeholder: '— Tất cả nhân viên —',
        allowClear: true,
        width: '220px',
        language: {
            searching: function() { return 'Đang tìm kiếm...'; },
            noResults: function() { return 'Không tìm thấy nhân viên'; }
        }
    }).on('select2:select select2:clear', function () {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
