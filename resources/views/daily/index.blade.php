@extends('layouts.app')
@section('title', 'Nhập công ' . \Carbon\Carbon::parse($date)->format('d/m/Y') . ' - KRIK')

@php
    $authUser   = auth()->user();
    $isManager  = in_array($authUser->role, ['admin', 'store_manager', 'hr']) 
                  || in_array($authUser->getGroupRoleName(), ['QLCH', 'CHP']);
    $canViewStats = in_array($authUser->role, ['admin', 'store_manager', 'hr', 'area_manager']) 
                    || in_array($authUser->getGroupRoleName(), ['QLCH', 'CHP']);
    $kpiTarget  = $dailyTarget ? (float)($dailyTarget->rebalanced_target ?: $dailyTarget->target_amount) : 0;
    $storeRev   = $totals['store_revenue'] ?? 0;
    $kpiStorePct= $kpiTarget > 0 ? round($storeRev / $kpiTarget * 100, 1) : 0;
    $dayLabel   = \Carbon\Carbon::parse($date)->locale('vi')->isoFormat('dddd, DD/MM/YYYY');
    $isWeekend  = \Carbon\Carbon::parse($date)->isoWeekday() >= 6;
@endphp

@section('content')
{{-- ═══ HEADER BAR ═══ --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-5 overflow-hidden">
    {{-- Top controls --}}
    <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2 flex-1 min-w-[200px]">
            <input type="date" id="picker_date" value="{{ $date }}"
                class="px-3 py-2 rounded-lg border-2 border-slate-100 outline-none font-bold text-slate-700 text-sm focus:border-blue-300"
                onchange="navigate()">
            <select id="picker_store"
                class="px-3 py-2 rounded-lg border-2 border-slate-100 outline-none font-bold text-slate-700 text-sm focus:border-blue-300"
                onchange="navigate()">
                <option value="">-- Chọn cửa hàng --</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->code }} – {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $dayLabel }} {{ $isWeekend ? '🏖' : '' }}</p>
        @if($isLocked)
            <span class="px-3 py-1.5 bg-rose-100 text-rose-600 rounded-full text-xs font-black">🔒 Đã khóa ngày</span>
        @endif
    </div>

    {{-- Stats row --}}
    @if($storeId)
    @if($canViewStats)
    <div class="grid grid-cols-2 md:grid-cols-5 gap-0 divide-x divide-slate-100">
        {{-- KPI target ngày --}}
        <div class="px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">KPI Ngày / Target</p>
            <div class="text-sm font-black {{ $kpiStorePct >= 100 ? 'text-emerald-600' : ($kpiStorePct >= 80 ? 'text-amber-500' : 'text-rose-500') }}">
                <span id="stat-kpi-pct">{{ $kpiStorePct }}</span>%
            </div>
            <div class="text-[9px] text-slate-400 mt-0.5" title="{{ number_format($kpiTarget, 0, ',', '.') }}đ">Target: <span class="font-bold text-slate-600">{{ number_format($kpiTarget, 0, ',', '.') }}đ</span></div>
        </div>
        {{-- Tổng DT cửa hàng --}}
        <div class="px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Tổng DT hôm nay</p>
            <div class="text-sm font-black text-emerald-700" id="stat-store-rev">{{ number_format($storeRev, 0, ',', '.') }}</div>
            <div class="text-[9px] text-slate-400 mt-0.5">Tổng DT cá nhân NV</div>
        </div>
        <div class="px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Khách hàng</p>
            <div class="text-lg font-black text-blue-700" id="stat-customers">{{ $totals['customers'] ?? 0 }}</div>
        </div>
        <div class="px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Số hóa đơn</p>
            <div class="text-lg font-black text-amber-600" id="stat-orders">{{ $totals['orders'] ?? 0 }}</div>
        </div>
        <div class="px-5 py-4">
            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Sản phẩm</p>
            <div class="text-lg font-black text-purple-600" id="stat-products">{{ $totals['products'] ?? 0 }}</div>
        </div>
    </div>
    @endif

    {{-- Action bar (QLCH only) --}}
    @if($isManager && !$isLocked)
    <div class="flex flex-wrap items-center gap-3 px-5 py-3 bg-slate-50 border-t border-slate-100">
        <p class="text-[10px] text-slate-400 flex-1">Khi khóa ngày, hệ thống sẽ tự <strong>tái phân bổ KPI</strong> các ngày còn lại trong tuần theo doanh thu thực tế hôm nay.</p>
        <button onclick="lockDay()"
            class="px-4 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-bold text-xs shadow transition-all ml-auto">
            🔒 Khóa ngày & Rebalance
        </button>
    </div>
    @endif
    @endif
</div>

{{-- ═══ BẢNG NHẬP CÔNG ═══ --}}
@if($storeId && $users->count())
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full border-collapse text-[11px] min-w-[1100px]" id="workTable">
        <thead>
            <tr class="bg-slate-800 text-white text-center text-[9px] uppercase font-bold tracking-wider">
                <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-700 sticky left-0 bg-slate-800 z-20 w-44">Nhân viên</th>
                <th colspan="3" class="px-2 py-2 border-r border-slate-700 bg-blue-800">Giờ công</th>
                <th colspan="3" class="px-2 py-2 border-r border-slate-700 bg-emerald-800">Doanh thu cá nhân (đ)</th>
                <th colspan="4" class="px-2 py-2 border-r border-slate-700 bg-slate-600">Số liệu phụ</th>
                <th rowspan="2" class="px-3 py-3 bg-amber-600 text-white w-28">KPI cá nhân</th>
                <th colspan="2" class="px-2 py-2 bg-rose-800">Hiệu suất</th>
                @if($isManager && !$isLocked)<th rowspan="2" class="px-2 bg-slate-700 w-8"></th>@endif
            </tr>
            <tr class="bg-slate-700 text-center text-[8px]">
                <th class="px-2 py-2 border-r border-slate-600 w-16 text-blue-300">🌅 Sáng</th>
                <th class="px-2 py-2 border-r border-slate-600 w-16 text-blue-300">☀️ Chiều</th>
                <th class="px-2 py-2 border-r border-slate-600 w-16 text-blue-300">🌙 Tối</th>
                <th class="px-2 py-2 border-r border-slate-600 w-24 text-emerald-300">Sáng</th>
                <th class="px-2 py-2 border-r border-slate-600 w-24 text-emerald-300">Chiều</th>
                <th class="px-2 py-2 border-r border-slate-600 w-24 text-emerald-300">Tối</th>
                <th class="px-2 py-2 border-r border-slate-600 w-12">KH</th>
                <th class="px-2 py-2 border-r border-slate-600 w-12">Thử</th>
                <th class="px-2 py-2 border-r border-slate-600 w-12">Đơn</th>
                <th class="px-2 py-2 border-r border-slate-600 w-12">SP</th>
                <th class="px-2 py-2 border-r border-rose-700 w-28 text-rose-300">Tổng DT</th>
                <th class="px-2 py-2 w-16 text-rose-300">SP/Bill</th>
                {{-- Không render thêm th nào ở đây — cột xóa đã dùng rowspan="2" từ row trên --}}
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($users as $user)
        @php
            $kd          = $kpiData[$user->id] ?? ['totalRev'=>0,'target'=>0,'kpiPct'=>0];
            $kpiColor    = $kd['kpiPct'] >= 100 ? 'text-emerald-600' : ($kd['kpiPct'] >= 80 ? 'text-amber-500' : 'text-rose-500');
            $isSales     = $user->position && $user->position->is_sales;
            $dk          = $user->daily_kpi;  // EmployeeDailyKpi record
            $totalOrders = $dk ? $dk->orders   : 0;
            $totalProds  = $dk ? $dk->products : 0;
            $spBill      = $totalOrders > 0 ? round($totalProds / $totalOrders, 1) : '-';
            $hasAnyHours = $user->shifts->sum('hours') > 0;  // có ít nhất 1 ca có giờ
        @endphp
        <tr class="{{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }} hover:bg-blue-50/20 transition-colors"
            data-user-id="{{ $user->id }}" data-user-name="{{ $user->full_name }}">

            {{-- Tên + chức danh --}}
            <td class="px-3 py-3 border-r border-slate-100 sticky left-0 {{ $loop->even ? 'bg-slate-50' : 'bg-white' }} z-10">
                <div class="font-bold text-slate-800 text-xs leading-tight">{{ $user->full_name }}</div>
                <div class="text-[8px] font-bold uppercase mt-0.5 {{ $isSales ? 'text-blue-500' : 'text-slate-400' }}">
                    {{ $user->position->code ?? 'STAFF' }}
                    @if(!$isSales)<span class="text-slate-300">· non-sales</span>@endif
                </div>
            </td>

            {{-- Giờ công từng ca --}}
            @foreach(['morning','afternoon','evening'] as $shift)
            <td class="px-1 py-1.5 border-r border-slate-100 bg-blue-50/30">
                @if(!$isLocked)
                <input type="number" step="0.5" min="0.5" max="6"
                    class="w-full text-center text-xs font-bold py-1 rounded outline-none focus:bg-blue-100 bg-transparent text-blue-700 transition-all"
                    value="{{ $user->shifts[$shift]->hours ?? '' }}"
                    data-user-id="{{ $user->id }}" data-shift="{{ $shift }}" data-field="hours"
                    oninput="if(parseFloat(this.value)<0||this.value==='-')this.value=''"
                    onblur="saveField(this)" placeholder="–">
                @else
                <span class="block text-center text-xs font-bold text-blue-700">{{ $user->shifts[$shift]->hours ?? '–' }}</span>
                @endif
            </td>
            @endforeach

            {{-- DT cá nhân từng ca — chỉ enable khi ca đó có giờ công VÀ là nhân viên sales --}}
            @foreach(['morning','afternoon','evening'] as $shift)
            @php
                $dtVal    = isset($user->shifts[$shift]) && $user->shifts[$shift]->personal_revenue > 0 ? (int)round($user->shifts[$shift]->personal_revenue) : '';
                $hasHours = isset($user->shifts[$shift]) && $user->shifts[$shift]->hours > 0;
            @endphp
            <td class="px-1 py-1.5 border-r border-slate-100 bg-emerald-50/20">
                @if(!$isSales)
                    {{-- Non-sales: chỉ hiển thị dấu gạch, không cho nhập DT --}}
                    <span class="block text-center text-[10px] text-slate-300 select-none">–</span>
                @elseif(!$isLocked)
                <input type="text" inputmode="numeric"
                    class="w-full text-right text-[10px] font-bold py-1 rounded outline-none bg-transparent text-emerald-700 transition-all
                           {{ $hasHours ? 'focus:bg-emerald-100 cursor-text' : 'opacity-30 cursor-not-allowed' }}"
                    value="{{ $dtVal && $hasHours ? number_format($dtVal, 0, ',', '.') : '' }}"
                    data-raw="{{ $dtVal }}"
                    data-user-id="{{ $user->id }}" data-shift="{{ $shift }}" data-field="personal_revenue"
                    {{ !$hasHours ? 'disabled' : 'onfocus="unfmt(this)" oninput="liveFormat(this)" onblur="fmtAndSave(this)"' }}
                    placeholder="–">
                @else
                <span class="block text-right text-[10px] font-bold text-emerald-700">
                    {{ $dtVal && $hasHours ? number_format($dtVal, 0, ',', '.') : '–' }}
                </span>
                @endif
            </td>
            @endforeach

            {{-- Số liệu phụ — chỉ sales mới được nhập, non-sales hiển thị dấu gạch --}}
            @foreach(['customers'=>'KH','fitting_rooms'=>'Thử','orders'=>'Đơn','products'=>'SP'] as $field => $label)
            <td class="px-1 py-1.5 border-r border-slate-100">
                @if(!$isSales)
                    {{-- Non-sales: chỉ hiển thị dấu gạch --}}
                    <span class="block text-center text-[10px] text-slate-300 select-none">–</span>
                @elseif(!$isLocked)
                <input type="number" min="0"
                    class="w-full text-center text-[10px] font-bold py-1 rounded outline-none bg-transparent text-slate-600 transition-all
                           {{ $hasAnyHours ? 'focus:bg-slate-100 cursor-text' : 'opacity-30 cursor-not-allowed' }}"
                    value="{{ $dk && $hasAnyHours ? ($dk->$field ?: '') : '' }}"
                    data-user-id="{{ $user->id }}" data-shift="morning" data-field="{{ $field }}"
                    {{ !$hasAnyHours ? 'disabled' : 'onblur="saveField(this)"' }}
                    placeholder="–">
                @else
                <span class="block text-center text-[10px] font-bold text-slate-600">{{ $dk ? ($dk->$field ?: '–') : '–' }}</span>
                @endif
            </td>
            @endforeach

            {{-- KPI cá nhân: chỉ hiện Target --}}
            <td class="px-3 py-3 text-center bg-amber-50 border-r border-amber-100 min-w-[120px]">
                @if($isSales)
                @if($kd['target'] > 0)
                <div class="font-black text-sm text-slate-700 leading-tight" id="kpi-target-{{ $user->id }}">
                    {{ number_format($kd['target'], 0, ',', '.') }}
                </div>
                @else
                <span class="text-slate-300 text-xs" id="kpi-target-{{ $user->id }}">–</span>
                @endif
                {{-- Hidden elements giữ id để JS vẫn update được --}}
                <span id="kpi-pct-{{ $user->id }}" class="hidden">{{ $kd['kpiPct'] }}</span>
                <span id="kpi-rev-{{ $user->id }}" class="hidden">{{ $kd['totalRev'] }}</span>
                @else
                <span class="text-[8px] text-slate-300">–</span>
                @endif
            </td>

            {{-- Hiệu suất: Tổng DT + % đạt bên dưới --}}
            <td class="px-3 py-3 text-right border-r border-slate-100">
                <div class="font-black text-xs text-rose-600" id="kpi-total-rev-{{ $user->id }}">
                    {{ $kd['totalRev'] > 0 ? number_format($kd['totalRev'], 0, ',', '.') : '–' }}
                </div>
                @if($isSales && $kd['target'] > 0)
                <div class="text-sm font-black mt-0.5 {{ $kpiColor }}" id="kpi-pct-rev-{{ $user->id }}">
                    {{ $kd['kpiPct'] }}%
                </div>
                @endif
            </td>
            <td class="px-2 py-3 text-center">
                <span class="text-[10px] font-bold text-slate-500" id="sp-bill-{{ $user->id }}">{{ $spBill }}</span>
            </td>

            {{-- Nút xóa (QLCH) --}}
            @if($isManager && !$isLocked)
            <td class="px-2 py-3 text-center">
                <button onclick="deleteEmployee({{ $user->id }}, '{{ addslashes($user->full_name) }}')"
                    class="w-6 h-6 flex items-center justify-center rounded-full bg-rose-100 hover:bg-rose-200 text-rose-400 hover:text-rose-600 transition-all text-xs font-black mx-auto"
                    title="Xóa dữ liệu ngày">✕</button>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>

        {{-- Footer tổng --}}
        @if($canViewStats)
        <tfoot class="bg-slate-800 text-white text-[9px] font-bold">
            <tr>
                <td class="px-4 py-2 sticky left-0 bg-slate-800 uppercase tracking-wider">Tổng</td>
                <td colspan="3" class="text-center border-r border-slate-700">
                    {{ number_format($users->sum(fn($u) => $u->shifts->sum('hours')), 1) }}h
                </td>
                <td colspan="3" class="text-right px-2 border-r border-slate-700 text-emerald-300">
                    {{ number_format($totals['store_revenue'] ?? 0, 0, ',', '.') }}
                </td>
                <td class="text-center border-r border-slate-700">{{ $totals['customers'] ?? 0 }}</td>
                <td class="text-center border-r border-slate-700">{{ $totals['fitting_rooms'] ?? 0 }}</td>
                <td class="text-center border-r border-slate-700">{{ $totals['orders'] ?? 0 }}</td>
                <td class="text-center border-r border-slate-700">{{ $totals['products'] ?? 0 }}</td>
                <td colspan="3" class="text-center text-amber-300">
                    KPI Store: {{ $kpiStorePct }}%
                </td>
                @if($isManager && !$isLocked)<td></td>@endif
            </tr>
        </tfoot>
        @endif
    </table>
    </div>
</div>
@elseif($storeId)
<div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400">
    <div class="text-3xl mb-2">👥</div>
    <p class="font-bold">Chưa có nhân viên nào trong cửa hàng này.</p>
</div>
@else
<div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400">
    <div class="text-3xl mb-2">🏪</div>
    <p class="font-bold">Chọn cửa hàng để bắt đầu nhập công.</p>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const STORE_ID  = '{{ $storeId }}';
const DATE      = '{{ $date }}';
const TOKEN     = '{{ csrf_token() }}';
const KPI_TARGET= {{ $kpiTarget }};

function navigate() {
    const d = document.getElementById('picker_date').value;
    const s = document.getElementById('picker_store').value;
    window.location.href = '?date=' + d + '&store_id=' + s;
}

// ── Save-on-blur — không reload ──
async function saveField(el) {
    let val   = el.value;
    const field = el.dataset.field;

    // Chặn số âm cho giờ công và số liệu phụ
    if (field === 'hours' || ['customers','fitting_rooms','orders','products'].includes(field)) {
        if (parseFloat(val) < 0 || val === '-') {
            el.value = '';
            val = '';
        }
    }

    // Validate giờ công từ 0.5 -> 6, lẻ .5
    if (field === 'hours' && val !== '') {
        const h = parseFloat(val);
        if (isNaN(h) || h < 0.5 || h > 6 || (h * 10) % 5 !== 0) {
            Swal.fire({
                title: 'Số giờ công không hợp lệ!',
                html: 'Mỗi ca chỉ được nhập từ <b>0.5</b> đến <b>6</b> giờ công,<br>và chỉ cho phép nhập lẻ <b>.5</b> (Ví dụ: 1.5, 2, 2.5...).',
                icon: 'warning',
                confirmButtonColor: '#3b82f6'
            });
            el.value = el.dataset.prev || '';
            return;
        }
    }

    // Với giờ công: cho phép gửi khi val rỗng (để backend xóa bản ghi)
    // Các field khác: bỏ qua nếu rỗng hoặc không đổi
    if (field !== 'hours' && val === '') return;
    if (val === el.dataset.prev) return;
    el.dataset.prev = val;

    el.style.outline = '2px solid #f59e0b';

    try {
        const res = await fetch('{{ route("fe.daily.update") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN },
            body: JSON.stringify({
                user_id   : el.dataset.userId,
                store_id  : STORE_ID,
                date      : DATE,
                shift_type: el.dataset.shift,
                field     : el.dataset.field,
                value     : val || 0,
            })
        });
        const data = await res.json();

        if (!res.ok || data.status === 'error') {
            Swal.fire({
                title: 'Lỗi lưu dữ liệu!',
                text: data.message || 'Có lỗi xảy ra khi cập nhật.',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            el.value = el.dataset.prev || '';
            el.style.outline = '2px solid #ef4444';
            setTimeout(() => el.style.outline = '', 2000);
            return;
        }

        el.style.outline = '2px solid #10b981';
        setTimeout(() => el.style.outline = '', 1200);

        if (data.all_kpi) updateAllKPI(data.all_kpi);
        if (data.totals)  updateTotals(data.totals);

        // Khi lưu giờ công → cập nhật enable/disable DT + số liệu phụ ngay
        if (el.dataset.field === 'hours') {
            updateShiftState(el.dataset.userId, el.dataset.shift, parseFloat(val) || 0);
        }
    } catch(e) {
        el.style.outline = '2px solid #ef4444';
        console.error(e);
    }
}

// ── Enable/disable DT + số liệu phụ theo giờ công ──
function updateShiftState(userId, shift, hours) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    // DT input của ca này
    const dtInput = row.querySelector(`input[data-shift="${shift}"][data-field="personal_revenue"]`);
    if (dtInput) {
        const active = hours > 0;
        dtInput.disabled = !active;
        dtInput.classList.toggle('opacity-30',        !active);
        dtInput.classList.toggle('cursor-not-allowed', !active);
        dtInput.classList.toggle('cursor-text',        active);
        if (active) {
            dtInput.addEventListener('focus',  function() { unfmt(this); }, { once: false });
            dtInput.addEventListener('input',  function() { liveFormat(this); }, { once: false });
            dtInput.addEventListener('blur',   function() { fmtAndSave(this); }, { once: false });
            // Restore event attrs
            dtInput.onfocus = () => unfmt(dtInput);
            dtInput.oninput = () => liveFormat(dtInput);
            dtInput.onblur  = () => fmtAndSave(dtInput);
        } else {
            dtInput.value  = '';
            dtInput.onfocus = null;
            dtInput.oninput = null;
            dtInput.onblur  = null;
        }
    }

    // Kiểm tra ALL ca trong row có giờ không → enable/disable secondary metrics
    const hourInputs    = row.querySelectorAll('input[data-field="hours"]');
    const hasAnyHours   = Array.from(hourInputs).some(inp => parseFloat(inp.value) > 0);
    const secFields     = ['customers', 'fitting_rooms', 'orders', 'products'];
    secFields.forEach(f => {
        const inp = row.querySelector(`input[data-field="${f}"]`);
        if (!inp) return;
        inp.disabled = !hasAnyHours;
        inp.classList.toggle('opacity-30',        !hasAnyHours);
        inp.classList.toggle('cursor-not-allowed', !hasAnyHours);
        inp.classList.toggle('cursor-text',        hasAnyHours);
        inp.onblur = hasAnyHours ? () => saveField(inp) : null;
        if (!hasAnyHours) inp.value = '';
    });
}

// ── Format tiền — luôn dùng dấu chấm bất kể locale trình duyệt ──
function fmtVND(n) {
    if (!n) return '';
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
// helpers cho input "Tổng DT CH"
function unfmtInput(el) {
    el.value = el.value.replace(/\./g, '').replace(/,/g, '');
    el.select();
}
function fmtInput(el) {
    const n = parseFloat(el.value.replace(/\./g, '').replace(/,/g, '')) || 0;
    el.value = n > 0 ? fmtVND(n) : '';
}
function unfmt(el) {
    // Focus: hiện số thô để dễ sửa
    const raw = el.value.replace(/\./g, '').replace(/,/g, '').replace(/\s/g, '');
    el.value = raw || '';
    el.select();
}
function liveFormat(el) {
    // Đếm số chữ số trước cursor (để restore sau khi format)
    const selStart    = el.selectionStart;
    const beforeCursor = el.value.substring(0, selStart).replace(/\D/g, '').length;

    // Lấy digits thuần
    const raw = el.value.replace(/\D/g, '');
    if (!raw) { el.value = ''; return; }

    // Format với dấu chấm
    const formatted = parseInt(raw, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    el.value = formatted;

    // Khôi phục cursor: đếm lại vị trí digit thứ n trong chuỗi đã format
    let digits = 0, newPos = formatted.length;
    for (let i = 0; i < formatted.length; i++) {
        if (/\d/.test(formatted[i])) {
            digits++;
            if (digits === beforeCursor) { newPos = i + 1; break; }
        }
    }
    try { el.setSelectionRange(newPos, newPos); } catch(e) {}
}
async function fmtAndSave(el) {
    const raw = el.value.replace(/\./g, '').replace(/,/g, '').replace(/\s/g, '');
    const num = parseFloat(raw) || 0;
    el.value = num > 0 ? fmtVND(num) : '';

    if (raw === el.dataset.prev) return;
    el.dataset.prev = raw;

    el.style.outline = '2px solid #f59e0b';
    try {
        const res = await fetch('{{ route("fe.daily.update") }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN },
            body   : JSON.stringify({
                user_id   : el.dataset.userId,
                store_id  : STORE_ID,
                date      : DATE,
                shift_type: el.dataset.shift,
                field     : el.dataset.field,
                value     : num,
            })
        });
        const data = await res.json();
        if (!res.ok || data.status === 'error') {
            Swal.fire({
                title: 'Lỗi lưu dữ liệu!',
                text: data.message || 'Có lỗi xảy ra khi cập nhật.',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            el.value = el.dataset.prev ? fmtVND(parseFloat(el.dataset.prev)) : '';
            el.style.outline = '2px solid #ef4444';
            setTimeout(() => el.style.outline = '', 2000);
            return;
        }
        el.style.outline = '2px solid #10b981';
        setTimeout(() => el.style.outline = '', 1200);
        if (data.all_kpi) updateAllKPI(data.all_kpi);
        if (data.totals)  updateTotals(data.totals);
    } catch(e) {
        el.style.outline = '2px solid #ef4444';
        console.error(e);
    }
}

// ── Cập nhật KPI từng NV trên DOM ──
function updateAllKPI(allKpi) {
    for (const [userId, kpi] of Object.entries(allKpi)) {
        const pct   = kpi.kpi_pct;
        const color = pct >= 100 ? '#059669' : pct >= 80 ? '#d97706' : '#e11d48';

        const elPct     = document.getElementById('kpi-pct-'      + userId);
        const elTgt     = document.getElementById('kpi-target-'   + userId);
        const elRev     = document.getElementById('kpi-rev-'      + userId);
        const elTotalRev= document.getElementById('kpi-total-rev-'+ userId);

        if (elPct)     { elPct.textContent = pct.toFixed(1) + '%'; elPct.style.color = color; }
        if (elTgt)       elTgt.textContent     = fmtVND(kpi.target);
        if (elRev)       elRev.textContent     = fmtVND(kpi.total_rev);
        if (elTotalRev)  elTotalRev.textContent= kpi.total_rev > 0 ? fmtVND(kpi.total_rev) : '–';
    }
}

// ── Cập nhật stats header ──
function updateTotals(t) {
    const rev    = t.store_revenue || 0;
    const pct    = KPI_TARGET > 0 ? (rev / KPI_TARGET * 100).toFixed(1) : 0;
    const color  = pct >= 100 ? '#059669' : pct >= 80 ? '#d97706' : '#e11d48';

    const elPct  = document.getElementById('stat-kpi-pct');
    const elRev  = document.getElementById('stat-store-rev');
    if (elPct) { elPct.textContent = pct; elPct.style.color = color; }
    if (elRev)   elRev.textContent = Math.round(rev).toLocaleString('vi-VN');

    const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
    set('stat-customers', t.customers || 0);
    set('stat-orders',    t.orders    || 0);
    set('stat-products',  t.products  || 0);
}

// ── Cân bằng KPI ──
async function equalizeKPI() {
    const rawRev  = document.getElementById('total_revenue_input').value.replace(/\./g, '').replace(/,/g, '');
    const totalRev = parseFloat(rawRev) || 0;
    if (!totalRev || !STORE_ID) {
        Swal.fire('Thiếu dữ liệu', 'Vui lòng nhập Tổng DT cửa hàng trước', 'warning');
        return;
    }
    const result = await Swal.fire({
        title: '⚡ Cân bằng KPI?',
        html : `Phân bổ <b>${fmtVND(totalRev)}đ</b><br>cho toàn bộ NV theo tỷ lệ giờ công?`,
        icon : 'question',
        showCancelButton: true,
        confirmButtonText: '⚡ Cân bằng',
        cancelButtonText : 'Hủy',
        confirmButtonColor: '#f59e0b',
    });
    if (!result.isConfirmed) return;

    const res  = await fetch('{{ route("fe.daily.equalize") }}', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN },
        body   : JSON.stringify({ date: DATE, store_id: STORE_ID, total_revenue: totalRev }),
    });
    const data = await res.json();
    if (data.status === 'success') {
        Swal.fire({ title: 'Thành công!', text: 'Đã cân bằng KPI.', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
    } else {
        Swal.fire('Lỗi', data.message, 'error');
    }
}

// ── Khóa ngày → tự động rebalance KPI tuần ──
async function lockDay() {
    const result = await Swal.fire({
        title: '🔒 Khóa ngày ' + DATE + '?',
        html : 'Sau khi khóa:<br>• Nhân viên <b>không thể sửa</b> dữ liệu nữa<br>• Hệ thống tự <b>tái phân bổ KPI</b> các ngày còn lại trong tuần',
        icon : 'warning',
        showCancelButton: true,
        confirmButtonText: '🔒 Khóa & Rebalance',
        cancelButtonText : 'Hủy',
        confirmButtonColor: '#334155',
    });
    if (!result.isConfirmed) return;

    const res = await fetch('{{ route("fe.daily.lock") }}', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN },
        body   : JSON.stringify({ date: DATE, store_id: STORE_ID }),
    });
    const data = await res.json();
    if (data.status === 'success') {
        Swal.fire({ title: 'Đã khóa!', text: 'KPI các ngày tới đã được tái phân bổ.', icon: 'success', timer: 1800, showConfirmButton: false })
            .then(() => location.reload());
    } else {
        Swal.fire('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
    }
}

// ── Xóa dữ liệu NV ──
async function deleteEmployee(userId, userName) {
    const result = await Swal.fire({
        title: 'Xóa dữ liệu?',
        html : `Xóa toàn bộ dữ liệu ngày <b>${DATE}</b> của <b>${userName}</b>?`,
        icon : 'warning',
        showCancelButton: true,
        confirmButtonText: '🗑 Xóa',
        cancelButtonText : 'Hủy',
        confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;

    const res = await fetch(`{{ url('/staff-shift-kpi/daily/records') }}/${userId}?store_id=${STORE_ID}&date=${DATE}`, {
        method : 'DELETE',
        headers: { 'X-CSRF-TOKEN': TOKEN },
    });
    const data = await res.json();
    if (data.status === 'success') {
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (row) { row.style.opacity = '0'; row.style.transition = 'opacity 0.3s'; setTimeout(() => row.remove(), 300); }
    }
}

// ── Ẩn spinner số trong input ──
</script>
<style>
    #workTable input[type=number]::-webkit-inner-spin-button,
    #workTable input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
    #workTable input[type=number] { -moz-appearance: textfield; }
    #workTable input { transition: outline 0.2s, background 0.2s; }
</style>
@endsection
