@extends('layouts.app')

@section('title', 'Cấu hình KPI tháng')

@section('content')

{{-- Danh sách đã cấu hình --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-slate-700 uppercase tracking-wide">Danh sách KPI đã cấu hình</h2>
        <button onclick="toggleForm()" class="bg-rose-500 text-white px-4 py-2 rounded-xl font-bold text-sm hover:bg-rose-600 transition-all shadow-sm shadow-rose-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Thiết lập KPI mới
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-left border-collapse table-compact">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4">Cửa hàng</th>
                    <th class="px-6 py-4">Tháng</th>
                    <th class="px-6 py-4 text-right">Tổng KPI</th>
                    <th class="px-6 py-4 text-center">Tỷ lệ Early:Late</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($configs as $config)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-3 font-bold text-slate-900">
                        <span class="bg-rose-50 text-rose-600 px-2 py-0.5 rounded text-xs font-bold mr-2">{{ $config->store->code }}</span>
                        {{ $config->store->name }}
                    </td>
                    <td class="px-6 py-3 text-slate-600">{{ $config->month }}</td>
                    <td class="px-6 py-3 text-right font-bold text-emerald-600">{{ number_format($config->total_target, 0, ',', '.') }}đ</td>
                    <td class="px-6 py-3 text-center">
                        @php
                            $dr = $config->daily_ratios ?? [];
                            $early = (($dr[1]??0)+($dr[2]??0)+($dr[3]??0)+($dr[4]??0));
                            $late  = (($dr[5]??0)+($dr[6]??0)+($dr[7]??0));
                        @endphp
                        <span class="text-xs text-slate-500">{{ round($early,1) }}% / {{ round($late,1) }}%</span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('fe.kpi-config.show', $config->id) }}" class="text-rose-500 hover:text-rose-700 text-xs font-bold bg-rose-50 px-3 py-1.5 rounded-lg hover:bg-rose-100 transition-colors">
                            Chi tiết & Điều chỉnh →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-300 text-sm">
                        Chưa có KPI nào được cấu hình. Nhấn "Thiết lập KPI mới" để bắt đầu.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- FORM TẠO MỚI (ẩn mặc định) --}}
<div id="setupForm" class="hidden">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-slate-800 text-white px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold">Thiết lập KPI tháng mới</h3>
            <button onclick="toggleForm()" class="text-slate-400 hover:text-white text-xl font-bold">✕</button>
        </div>

        <form action="{{ route('fe.kpi-config.store') }}" method="POST" onsubmit="return prepareSubmit()" class="p-6 space-y-8">
            @csrf

            {{-- BƯỚC 1: Thông tin cơ bản --}}
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="bg-rose-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px]">1</span>
                    Thông tin cơ bản
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Cửa hàng</label>
                        <select name="store_id" id="sel_store" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 focus:border-rose-400 outline-none font-bold text-slate-700" required>
                            <option value="">-- Chọn cửa hàng --</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}">{{ $s->code }} – {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tháng áp dụng</label>
                        <input type="month" name="month" id="sel_month" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 focus:border-rose-400 outline-none font-bold text-slate-700" value="{{ date('Y-m') }}" required onchange="buildWeekGrid()">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tổng KPI tháng (VND)</label>
                        <input type="text" id="total_target_display" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 focus:border-rose-400 outline-none font-bold text-slate-800 text-lg" placeholder="VD: 1.000.000.000" onkeyup="formatCurrency(this); previewCalc()" required>
                        <input type="hidden" name="total_target" id="total_target_hidden">
                    </div>
                </div>
            </div>


            {{-- BƯỚC 2: Tỷ trọng tuần (5 tuần, sum = 100%) --}}
            <div class="border-t border-slate-100 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <span class="bg-rose-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px]">2</span>
                        Tỷ trọng tuần (5 tuần, tổng = 100%)
                    </p>
                    <span id="week_sum_badge" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">Tổng: 100%</span>
                </div>
                <div class="grid grid-cols-5 gap-3">
                    @for($w=1; $w<=5; $w++)
                    <div class="bg-slate-50 rounded-xl p-4 text-center">
                        <div class="text-[9px] font-bold text-slate-400 uppercase mb-2">Tuần {{ $w }}</div>
                        <div class="flex items-center justify-center gap-1">
                            <input type="number" name="week_weights[{{ $w }}]" step="0.01" min="0" max="100"
                                value="20.00"
                                class="week-weight-input w-20 px-2 py-1.5 rounded-lg border-2 border-blue-100 focus:border-blue-400 outline-none font-bold text-blue-600 text-center"
                                oninput="updateWeekSum(); previewCalc()"
                                onblur="this.value=parseFloat(this.value||0).toFixed(2); updateWeekSum(); previewCalc()">
                            <span class="text-xs text-slate-400">%</span>
                        </div>
                        <div class="text-[10px] font-mono text-slate-400 mt-2 week-amt">--</div>
                    </div>
                    @endfor
                </div>
            </div>

            {{-- BƯỚC 3: Tỷ trọng ngày trong tuần (T2–CN, sum = 100%) --}}
            <div class="border-t border-slate-100 pt-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="bg-rose-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px]">3</span>
                    Tỷ trọng ngày trong tuần (T2–CN, tổng = 100%)
                </p>
                <div class="flex flex-wrap gap-2 mb-4" id="preset_btns">
                    <button type="button" data-preset="equal" onclick="applyPreset('equal')" class="preset-btn px-4 py-2 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-700 font-bold text-xs hover:bg-blue-100 transition-all">🎯 Mỗi ngày bằng nhau</button>
                    <button type="button" data-preset="weekend_heavy" onclick="applyPreset('weekend_heavy')" class="preset-btn px-4 py-2 rounded-xl border-2 border-purple-200 bg-purple-50 text-purple-700 font-bold text-xs hover:bg-purple-100 transition-all">🔥 Cuối tuần mạnh (1:2)</button>
                    <button type="button" data-preset="balanced" onclick="applyPreset('balanced')" class="preset-btn px-4 py-2 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-700 font-bold text-xs hover:bg-emerald-100 transition-all">⚖️ Cân bằng (1:1.5)</button>
                    <button type="button" data-preset="custom" onclick="applyPreset('custom')" class="preset-btn px-4 py-2 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-600 font-bold text-xs hover:bg-slate-100 transition-all">✏️ Tùy chỉnh tay</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Hệ số Early (T2–T5)</label>
                        <input type="number" id="early_ratio" name="weekday_ratio" step="0.01" min="0.1" max="99" value="1"
                            class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 focus:border-blue-400 outline-none font-bold text-blue-700 text-center text-lg"
                            oninput="recalcDailyRatios()"
                            onblur="this.value=parseFloat(this.value||1).toFixed(2); recalcDailyRatios()">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Hệ số Late (T6–CN)</label>
                        <input type="number" id="late_ratio" name="weekend_ratio" step="0.01" min="0.1" max="99" value="1.5"
                            class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 focus:border-rose-400 outline-none font-bold text-rose-700 text-center text-lg"
                            oninput="recalcDailyRatios()"
                            onblur="this.value=parseFloat(this.value||1).toFixed(2); recalcDailyRatios()">
                    </div>
                    <div class="md:col-span-2 bg-slate-50 rounded-xl p-4">
                        <p class="text-[10px] text-slate-400 font-bold uppercase mb-2">Preview % từng ngày</p>
                        <div class="grid grid-cols-7 gap-1">
                            @foreach(['T2','T3','T4','T5','T6','T7','CN'] as $d)
                            <div class="text-center">
                                <div class="text-[8px] font-bold {{ in_array($d,['T6','T7','CN']) ? 'text-rose-400' : 'text-slate-400' }}">{{ $d }}</div>
                                <div class="text-[10px] font-bold text-slate-600 day-pct">--</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- BƯỚC 4: Tỷ trọng ca (Sáng / Chiều / Tối, weekday vs weekend riêng) --}}
            <div class="border-t border-slate-100 pt-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="bg-rose-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px]">4</span>
                    Tỷ trọng ca (Sáng / Chiều / Tối) — mỗi nhóm tổng = 100%
                </p>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs font-bold text-slate-500">Ngày thường (T2–T5)</p>
                            <span id="wd_sum_badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Tổng: 100%</span>
                        </div>
                        <div class="space-y-2">
                            @foreach(['morning'=>'Ca Sáng','afternoon'=>'Ca Chiều','evening'=>'Ca Tối'] as $k=>$lbl)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-20">{{ $lbl }}</span>
                                <input type="number" name="shift_weekday[{{ $k }}]" step="1" min="0" max="100"
                                    value="{{ ['morning'=>10,'afternoon'=>36,'evening'=>54][$k] }}"
                                    class="shift-wd flex-1 px-3 py-2 rounded-lg border-2 border-purple-100 focus:border-purple-400 outline-none font-bold text-purple-700 text-center"
                                    oninput="validateShiftSum('wd')">
                                <span class="text-xs text-slate-400">%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs font-bold text-slate-500">Cuối tuần (T6–CN)</p>
                            <span id="we_sum_badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Tổng: 100%</span>
                        </div>
                        <div class="space-y-2">
                            @foreach(['morning'=>'Ca Sáng','afternoon'=>'Ca Chiều','evening'=>'Ca Tối'] as $k=>$lbl)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-20">{{ $lbl }}</span>
                                <input type="number" name="shift_weekend[{{ $k }}]" step="1" min="0" max="100"
                                    value="{{ ['morning'=>12,'afternoon'=>45,'evening'=>43][$k] }}"
                                    class="shift-we flex-1 px-3 py-2 rounded-lg border-2 border-rose-100 focus:border-rose-400 outline-none font-bold text-rose-700 text-center"
                                    oninput="validateShiftSum('we')">
                                <span class="text-xs text-slate-400">%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>


            <div class="border-t border-slate-100 pt-6 flex justify-end gap-3">
                <button type="button" onclick="toggleForm()" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Hủy</button>
                <button type="submit" class="bg-rose-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-rose-600 shadow-lg shadow-rose-100 transition-all">
                    ✓ TẠO CẤU HÌNH & TÍNH TOÁN
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle form
function toggleForm() {
    const f = document.getElementById('setupForm');
    f.classList.toggle('hidden');
    if (!f.classList.contains('hidden')) {
        recalcDailyRatios();
        previewCalc();
        f.scrollIntoView({ behavior: 'smooth' });
    }
}

// Format currency
function formatCurrency(input) {
    let v = input.value.replace(/\D/g, '');
    input.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    document.getElementById('total_target_hidden').value = v;
    previewCalc();
}

// ============ PRESET ACTIVE STATE ============
const PRESET_STYLES = {
    active:   'ring-2 ring-offset-1 ring-slate-800 shadow-md scale-[1.03]',
    inactive: ''
};
let currentPreset = 'balanced';

function applyPreset(type) {
    currentPreset = type;
    // Highlight active button
    document.querySelectorAll('.preset-btn').forEach(btn => {
        const isActive = btn.dataset.preset === type;
        btn.classList.toggle('ring-2', isActive);
        btn.classList.toggle('ring-offset-1', isActive);
        btn.classList.toggle('shadow-md', isActive);
        btn.style.transform = isActive ? 'scale(1.04)' : '';
    });

    const earlyEl = document.getElementById('early_ratio');
    const lateEl  = document.getElementById('late_ratio');
    const isCustom = type === 'custom';
    earlyEl.readOnly = !isCustom;
    lateEl.readOnly  = !isCustom;
    earlyEl.classList.toggle('bg-slate-50', !isCustom);
    lateEl.classList.toggle('bg-slate-50', !isCustom);

    if (type === 'equal')          { earlyEl.value = 1.0; lateEl.value = 1.0; }
    else if (type === 'weekend_heavy') { earlyEl.value = 1.0; lateEl.value = 2.0; }
    else if (type === 'balanced')  { earlyEl.value = 1.0; lateEl.value = 1.5; }
    // 'custom' → keep current values, allow editing

    recalcDailyRatios();
    previewCalc();
}

// ============ DAY RATIO PREVIEW ============
function recalcDailyRatios() {
    const e = parseFloat(document.getElementById('early_ratio').value) || 1;
    const l = parseFloat(document.getElementById('late_ratio').value) || 1;
    // 4 ngày early (T2-T5), 3 ngày late (T6-CN)
    const total = (e * 4) + (l * 3);
    if (total <= 0) return;
    // Tính % chính xác từng ngày
    const eachEarly = (e / total * 100);
    const eachLate  = (l / total * 100);
    // Adjust last item để tổng = 100% chính xác
    const pcts = [eachEarly, eachEarly, eachEarly, eachEarly, eachLate, eachLate, eachLate];
    const sumPct = pcts.reduce((a,b)=>a+b, 0);
    pcts[6] += (100 - sumPct); // bù phần lẻ vào CN

    document.querySelectorAll('.day-pct').forEach((el, i) => {
        el.textContent = pcts[i].toFixed(2) + '%';
        el.className = 'text-[10px] font-bold day-pct ' + (i >= 4 ? 'text-rose-600' : 'text-blue-600');
    });
}

// ============ WEEK SUM BADGE ============
function updateWeekSum() {
    const inputs = document.querySelectorAll('.week-weight-input');
    const total  = Array.from(inputs).reduce((s, i) => s + (parseFloat(i.value)||0), 0);
    const badge  = document.getElementById('week_sum_badge');
    const ok     = Math.abs(total - 100) < 0.5;
    badge.textContent = 'Tổng: ' + total.toFixed(2) + '%';
    badge.className   = 'text-xs font-bold px-3 py-1 rounded-full ' + (ok ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600 animate-pulse');
    // Tô đỏ border các ô input khi tổng sai
    inputs.forEach(inp => {
        inp.classList.toggle('border-rose-400', !ok);
        inp.classList.toggle('border-blue-100', ok);
    });
}

// ============ WEEK PREVIEW AMOUNT ============
function previewCalc() {
    const total  = parseInt(document.getElementById('total_target_hidden').value) || 0;
    const inputs = document.querySelectorAll('.week-weight-input');
    const amts   = document.querySelectorAll('.week-amt');
    inputs.forEach((inp, i) => {
        const pct = parseFloat(inp.value) || 0;
        const amt = total * pct / 100;
        amts[i].textContent = amt > 0 ? (amt/1000000).toFixed(1) + ' tr' : '--';
    });
}

// ============ SHIFT SUM VALIDATION ============
function validateShiftSum(group) {
    const cls   = group === 'wd' ? '.shift-wd' : '.shift-we';
    const badge = document.getElementById(group === 'wd' ? 'wd_sum_badge' : 'we_sum_badge');
    const total = Array.from(document.querySelectorAll(cls)).reduce((s,i) => s + (parseFloat(i.value)||0), 0);
    const ok    = Math.abs(total - 100) < 0.5;
    badge.textContent = 'Tổng: ' + total.toFixed(0) + '%';
    badge.className   = 'text-[10px] font-bold px-2 py-0.5 rounded-full ' + (ok ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600 animate-pulse');
    // Đổi màu border input để highlight lỗi
    document.querySelectorAll(cls).forEach(inp => {
        inp.classList.toggle('border-rose-400', !ok);
        inp.classList.toggle(group==='wd'?'border-purple-100':'border-rose-100', ok);
    });
}

// ============ SUBMIT VALIDATION ============
function prepareSubmit() {
    const h = document.getElementById('total_target_hidden');
    if (!h.value) h.value = document.getElementById('total_target_display').value.replace(/\D/g,'');
    if (!h.value) { alert('Vui lòng nhập tổng KPI!'); return false; }

    const weekTotal = Array.from(document.querySelectorAll('.week-weight-input')).reduce((s,i)=>s+(parseFloat(i.value)||0),0);
    if (Math.abs(weekTotal - 100) > 0.5) { alert('Tổng tỷ trọng tuần phải = 100%! Hiện tại: ' + weekTotal.toFixed(1) + '%'); return false; }

    const wdTotal = Array.from(document.querySelectorAll('.shift-wd')).reduce((s,i)=>s+(parseFloat(i.value)||0),0);
    if (Math.abs(wdTotal - 100) > 0.5) { alert('Tổng tỷ trọng ca Ngày thường phải = 100%! Hiện tại: ' + wdTotal + '%'); return false; }

    const weTotal = Array.from(document.querySelectorAll('.shift-we')).reduce((s,i)=>s+(parseFloat(i.value)||0),0);
    if (Math.abs(weTotal - 100) > 0.5) { alert('Tổng tỷ trọng ca Cuối tuần phải = 100%! Hiện tại: ' + weTotal + '%'); return false; }

    return true;
}

// ============ INIT ============
applyPreset('balanced'); // Mặc định chọn Cân bằng
updateWeekSum();
validateShiftSum('wd');
validateShiftSum('we');

@if($configs->isEmpty())
document.getElementById('setupForm').classList.remove('hidden');
@endif
</script>
@endsection
