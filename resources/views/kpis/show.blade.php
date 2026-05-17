@extends('layouts.app')
@section('title', 'Quản lý KPI')
@section('has_local_alert', true)
@section('content')
@php
    $dr  = []; foreach(($dailyRatios??$config->daily_ratios??[]) as $k=>$v) { $dr[(int)$k]=(float)$v; }
    $wr  = []; foreach(($weeklyRatios??$config->weekly_ratios??[]) as $k=>$v) { $wr[(int)$k]=(float)$v; }
    $swd = $config->shift_ratios_weekday ?? ['morning'=>10,'afternoon'=>36,'evening'=>54];
    $swe = $config->shift_ratios_weekend ?? ['morning'=>12,'afternoon'=>45,'evening'=>43];
    $total = $config->total_target;
    for($i=1;$i<=7;$i++) if(!isset($dr[$i]) || $dr[$i]<=0) $dr[$i]=1.0;
    for($i=1;$i<=5;$i++) if(!isset($wr[$i])) $wr[$i]=20.0;
    // Tính earlyPct/latePct (% tổng 100) từ đơn vị lưu trong dr
    $eu = $dr[1]; $lu = $dr[5];
    $earlyPct = round($eu / ($eu + $lu) * 100, 2);
    $latePct  = round(100 - $earlyPct, 2);
@endphp

{{-- Breadcrumb + Back --}}
<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <div class="flex items-center gap-2">
        <a href="{{ route('fe.kpi-config.index') }}"
           class="flex items-center gap-1.5 text-slate-500 hover:text-slate-800 text-xs font-black transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Danh sách KPI
        </a>
        <span class="text-slate-300">/</span>
        <span class="text-xs font-black text-rose-500">{{ $config->store->code }}</span>
        <span class="text-slate-300">/</span>
        <span class="text-xs font-bold text-slate-600">{{ \Carbon\Carbon::parse($config->month.'-01')->locale('vi')->isoFormat('MMMM Y') }}</span>
    </div>
    <form action="{{ route('fe.kpi-config.regenerate', $config->id) }}" method="POST">@csrf
        <button type="submit" class="px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100">🔄 Tính lại targets</button>
    </form>
</div>

@if(session('success'))<div class="mb-2 bg-emerald-50 border-l-4 border-emerald-400 text-emerald-700 text-xs px-4 py-2 rounded-r-lg">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-2 bg-rose-50 border-l-4 border-rose-400 text-rose-700 text-xs px-4 py-2 rounded-r-lg">{{ session('error') }}</div>@endif

<form action="{{ route('fe.kpi-config.update-matrix', $config->id) }}" method="POST" id="mainForm">
@csrf

{{-- ═══ CONFIG PANEL ═══ --}}
<div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-3">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
        {{-- Tổng KPI --}}
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tổng KPI Tháng</p>
            <div class="flex items-center gap-2">
                {{-- Input hiển thị có dấu chấm, hidden input gửi form --}}
                <input type="text" id="inp_total_display"
                    value="{{ number_format($total,0,',','.') }}"
                    class="flex-1 px-3 py-2 rounded-lg border border-emerald-200 focus:border-emerald-400 outline-none font-black text-emerald-700 text-sm text-left"
                    oninput="onTotalInput(this)" onfocus="this.value=this.value.replace(/\./g,'')" onblur="formatTotalDisplay()">
                <input type="hidden" name="total_target" id="inp_total" value="{{ $total }}">
                <span class="text-xs text-slate-400 font-bold">đ</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">= <span id="total_fmt" class="font-bold text-emerald-600">{{ number_format($total,0,',','.') }}</span>đ</p>
        </div>
        {{-- Ca ngày thường --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Ca Ngày Thường</p>
                <span id="wd_sum_badge" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">100%</span>
            </div>
            <div class="flex gap-2">
                @foreach(['morning'=>'🌅','afternoon'=>'☀️','evening'=>'🌙'] as $k=>$icon)
                <div class="flex-1 text-center">
                    <span class="text-[9px] text-slate-400 block">{{ $icon }}</span>
                    <input type="number" name="shift_weekday[{{ $k }}]" value="{{ number_format($swd[$k]??0,2,'.','') }}" step="0.01" min="0" max="100"
                        class="wd-shift w-full px-1 py-1 rounded border border-slate-200 font-bold text-amber-700 text-center text-xs outline-none" oninput="checkShift('wd')">
                </div>
                @endforeach
            </div>
        </div>
        {{-- Ca cuối tuần --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Ca Cuối Tuần</p>
                <span id="we_sum_badge" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">100%</span>
            </div>
            <div class="flex gap-2">
                @foreach(['morning'=>'🌅','afternoon'=>'☀️','evening'=>'🌙'] as $k=>$icon)
                <div class="flex-1 text-center">
                    <span class="text-[9px] text-slate-400 block">{{ $icon }}</span>
                    <input type="number" name="shift_weekend[{{ $k }}]" value="{{ number_format($swe[$k]??0,2,'.','') }}" step="0.01" min="0" max="100"
                        class="we-shift w-full px-1 py-1 rounded border border-slate-200 font-bold text-indigo-700 text-center text-xs outline-none transition-colors" oninput="checkShift('we')">
                </div>
                @endforeach
            </div>
        </div>
        {{-- Phân bổ ngày --}}
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tỷ lệ KPI ngày (<span class="text-blue-500">Ngày thường</span> : <span class="text-rose-500">Cuối tuần</span>)</p>
            <div class="flex gap-2 mb-2 items-center">
                <div class="flex-1 text-center">
                    <p class="text-[9px] text-blue-400 font-bold mb-0.5">T2–T5 <span class="opacity-50">(%)</span></p>
                    <input type="number" id="early_pct" value="{{ $earlyPct }}" step="0.01" min="0.01" max="99.99"
                        class="w-full px-2 py-1.5 rounded border border-blue-200 font-black text-blue-600 text-center text-sm outline-none focus:border-blue-400 transition-colors"
                        oninput="onEarlyChange()">
                </div>
                <span class="text-slate-400 font-black text-lg mt-4">:</span>
                <div class="flex-1 text-center">
                    <p class="text-[9px] text-rose-400 font-bold mb-0.5">T6–CN <span class="opacity-50">(%)</span></p>
                    <input type="number" id="late_pct" value="{{ $latePct }}" step="0.01" min="0.01" max="99.99"
                        class="w-full px-2 py-1.5 rounded border border-rose-200 font-black text-rose-600 text-center text-sm outline-none focus:border-rose-400 transition-colors"
                        oninput="onLateChange()">
                </div>
            </div>
            <div class="flex gap-1">
                <button type="button" id="btn_preset_equal" onclick="applyPreset('equal')"
                    class="flex-1 py-1 rounded text-[9px] font-bold border transition-all bg-slate-100 text-slate-600 border-slate-200 hover:bg-blue-100 hover:text-blue-700 hover:border-blue-300">
                    = Bằng nhau
                </button>
                <button type="button" id="btn_preset_weekend" onclick="applyPreset('strong_weekend')"
                    class="flex-1 py-1 rounded text-[9px] font-bold border transition-all bg-slate-100 text-slate-600 border-slate-200 hover:bg-rose-100 hover:text-rose-700 hover:border-rose-300">
                    W+ Cuối tuần mạnh
                </button>
            </div>
            <p class="text-[9px] text-center mt-1"><span id="unit_badge" class="font-bold text-emerald-600">{{ $earlyPct }}% : {{ $latePct }}%</span></p>
        </div>
    </div>
</div>

{{-- ═══ KPI MATRIX TABLE ═══ --}}
<div class="bg-white rounded-xl border border-slate-100 overflow-x-auto shadow-sm">
<table class="w-full text-[11px] border-collapse" id="kpiMatrix">
<thead>
    <tr class="bg-slate-800 text-white text-center">
        <th class="px-3 py-2 text-left border-r border-slate-700 whitespace-nowrap">Tuần</th>
        <th class="px-3 py-2 border-r border-slate-700 whitespace-nowrap">
            KPI Tuần
            <br><span id="wk_badge" class="text-[9px] font-normal text-emerald-300">Tổng: 100%</span>
            <br><button type="button" onclick="autoDistributeWeeks()" class="mt-0.5 px-1.5 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-white text-[8px] font-bold transition-all" title="Phân bổ % theo số ngày thực tế">⚖ Theo ngày</button>
        </th>
        <th class="px-2 py-2 border-r border-slate-600 bg-blue-900" colspan="4">Ngày thường (T2–T5)</th>
        <th class="px-2 py-2 bg-rose-900" colspan="3">Cuối tuần (T6–CN)</th>
    </tr>
    <tr class="bg-slate-700 text-white text-center text-[10px]">
        <th class="border-r border-slate-600"></th><th class="border-r border-slate-600"></th>
        @foreach([1=>'T2',2=>'T3',3=>'T4',4=>'T5'] as $d=>$n) <th class="px-2 py-1 border-r border-slate-600 bg-blue-900/80">{{ $n }}</th> @endforeach
        @foreach([5=>'T6',6=>'T7',7=>'CN'] as $d=>$n) <th class="px-2 py-1 {{ !$loop->last?'border-r border-slate-600':'' }} bg-rose-900/80">{{ $n }}</th> @endforeach
    </tr>
</thead>
<tbody>
@for($w=1;$w<=5;$w++)
    @php
        $weekData = $weeks[$w] ?? ['weight'=>$wr[$w]??20,'targets'=>[]];
        $weekWt   = (float)($wr[$w] ?? 20);
        $weekAmt  = round($total * $weekWt / 100);
        $tgts     = collect($weekData['targets']);
        $byDow=[]; $presentDows=[]; $dateMap=[];
        foreach($tgts as $t) {
            $dow=\Carbon\Carbon::parse($t->date)->isoWeekday();
            $byDow[$dow]=($byDow[$dow]??0)+(float)$t->target_amount;
            $dateMap[$dow]=\Carbon\Carbon::parse($t->date)->format('d/m');
            if(!in_array($dow,$presentDows)) $presentDows[]=$dow;
        }
        sort($presentDows);
    @endphp
    <tr class="{{ $w%2?'bg-white':'bg-slate-50/60' }} hover:bg-blue-50/20" data-week-row="{{ $w }}" data-days="{{ implode(',',$presentDows) }}">
        <td class="px-3 py-3 font-black text-slate-700 border-r border-slate-100">Tuần {{ $w }}</td>
        <td class="px-2 py-2 border-r border-slate-100 text-center min-w-[130px]">
            <div class="flex flex-col items-center gap-1">
                <div class="flex items-center gap-1">
                    <input type="number" name="week_weights[{{ $w }}]" value="{{ number_format($weekWt,2,'.','') }}" step="0.01" min="0" max="100"
                        class="week-w w-20 px-2 py-1 rounded border border-blue-200 font-black text-blue-700 text-center text-xs outline-none shadow-sm" oninput="recalc()">
                    <span class="text-[9px] font-bold text-slate-400">%</span>
                </div>
                <div class="week-amt font-black text-emerald-700 text-xs" data-w="{{ $w }}">{{ number_format($weekAmt,0,',','.') }}</div>
                <div class="week-days-badge text-[8px] font-bold {{ count($presentDows)<7 ? 'text-amber-500' : 'text-slate-300' }}" data-w="{{ $w }}">{{ count($presentDows) }} ngày</div>
            </div>
        </td>
        @foreach([1,2,3,4,5,6,7] as $d)
        @php $isWE = $d>=5; @endphp
        <td class="px-1 py-2 border-r border-slate-100 text-center {{ $isWE?'bg-rose-50/20':'bg-blue-50/20' }} {{ $d==7?'border-r-0':'' }} min-w-[85px]">
            <div class="flex flex-col items-center gap-1.5 py-1">
                @if(in_array($d, $presentDows))
                    <span class="text-[9px] {{ $isWE?'text-rose-500':'text-blue-500' }} font-black uppercase tracking-tighter">{{ $dateMap[$d] }}</span>
                    {{-- Tất cả tuần đều hiển thị effective % (JS sẽ cập nhật) --}}
                    <span class="font-black {{ $isWE?'text-rose-600':'text-blue-600' }} day-pct text-[11px] bg-white/50 px-2 py-0.5 rounded border {{ $isWE?'border-rose-100':'border-blue-100' }} shadow-sm" data-dow="{{ $d }}" data-w="{{ $w }}">{{ number_format($dr[$d]??14.28,2) }}%</span>
                    <div class="day-kpi text-[12px] font-mono font-black {{ $isWE?'text-rose-900':'text-blue-900' }} mt-0.5 tracking-tight" data-w="{{ $w }}" data-dow="{{ $d }}" data-present="1">
                        {{ number_format(round($byDow[$d]??0),0,',','.') }}
                    </div>
                @else
                    <div class="h-[52px] flex items-center justify-center opacity-10"><span class="text-slate-400 font-bold">—</span></div>
                @endif
            </div>
        </td>
        @endforeach
    </tr>
@endfor
<tr class="bg-slate-800 text-white text-xs font-bold">
    <td class="px-3 py-3 border-r border-slate-700">TỔNG</td>
    <td class="px-2 py-3 border-r border-slate-700 text-center"><span id="wk_sum">100%</span></td>
    <td class="px-2 py-3 text-center" colspan="7"><span id="day_sum_badge" class="font-black text-emerald-300 text-sm"></span></td>
</tr>
</tbody>
</table>
</div>

<div class="mt-4 flex justify-between items-center">
    <p class="text-[10px] text-slate-400 italic">💡 Mỗi ô ngày hiển thị % hiệu quả trong tuần đó (tổng = 100%). Chỉnh tỷ lệ qua bảng Phân bổ bên trên.</p>
    {{-- Hidden inputs gi\u1eef \u0111\u01a1n v\u1ecb t\u1eebng ng\u00e0y \u0111\u1ec3 submit form --}}
    @for($d=1;$d<=7;$d++)
    <input type="hidden" name="day_weights[{{ $d }}]" class="day-w" data-dow="{{ $d }}" value="{{ $dr[$d] }}">
    @endfor
    <button type="submit" onclick="return validateForm()" class="bg-rose-500 text-white px-10 py-3 rounded-xl font-black text-sm hover:bg-rose-600 shadow-lg active:scale-95 transition-all uppercase tracking-tight">✓ LƯU CẤU HÌNH KPI</button>
</div>
</form>

<script>
let TOTAL = {{ $total }};

// ── T\u1ed5ng KPI: input text c\u00f3 d\u1ea5u ch\u1ea5m ──
function onTotalInput(el) {
    const raw = el.value.replace(/[^0-9]/g, '');
    el.value = raw;
    TOTAL = parseInt(raw) || 0;
    document.getElementById('inp_total').value = TOTAL;
    document.getElementById('total_fmt').textContent = TOTAL.toLocaleString('vi-VN');
    recalc();
}
function formatTotalDisplay() {
    const el = document.getElementById('inp_total_display');
    const num = parseInt(el.value.replace(/[^0-9]/g,'')) || 0;
    el.value = num.toLocaleString('vi-VN');
    document.getElementById('inp_total').value = num;
    TOTAL = num;
    document.getElementById('total_fmt').textContent = num.toLocaleString('vi-VN');
}

// L\u1ea5y \u0111\u01a1n v\u1ecb t\u1eebng ng\u00e0y t\u1eeb hidden inputs
function getDW(){
    const o={};
    document.querySelectorAll('input.day-w').forEach(i=>{ o[parseInt(i.dataset.dow)] = parseFloat(i.value)||1; });
    return o;
}
function getWW(){ return Array.from(document.querySelectorAll('input.week-w')).map(i=>parseFloat(i.value)||0); }

// \u00c1p % v\u00e0o inputs v\u00e0 c\u1eadp nh\u1eadt hidden day-w
function applyDayRatio(earlyPct, latePct) {
    earlyPct = Math.round(earlyPct * 100) / 100;
    latePct  = Math.round(latePct  * 100) / 100;
    document.getElementById('early_pct').value = earlyPct.toFixed(2);
    document.getElementById('late_pct').value  = latePct.toFixed(2);
    document.getElementById('unit_badge').textContent = earlyPct.toFixed(2) + '% : ' + latePct.toFixed(2) + '%';
    [1,2,3,4].forEach(dow => {
        const inp = document.querySelector(`input.day-w[data-dow="${dow}"]`);
        if(inp) inp.value = earlyPct.toFixed(2);
    });
    [5,6,7].forEach(dow => {
        const inp = document.querySelector(`input.day-w[data-dow="${dow}"]`);
        if(inp) inp.value = latePct.toFixed(2);
    });
}

function onEarlyChange() {
    let ep = parseFloat(document.getElementById('early_pct').value) || 0;
    ep = Math.min(99.99, Math.max(0.01, parseFloat(ep.toFixed(2))));
    const lp = parseFloat((100 - ep).toFixed(2));
    applyDayRatio(ep, lp);
    setPresetActive(null);
    recalc();
}
function onLateChange() {
    let lp = parseFloat(document.getElementById('late_pct').value) || 0;
    lp = Math.min(99.99, Math.max(0.01, parseFloat(lp.toFixed(2))));
    const ep = parseFloat((100 - lp).toFixed(2));
    applyDayRatio(ep, lp);
    setPresetActive(null);
    recalc();
}

function setPresetActive(name) {
    const BASE = 'flex-1 py-1 rounded text-[9px] font-bold border transition-all';
    const eb = document.getElementById('btn_preset_equal');
    const wb = document.getElementById('btn_preset_weekend');
    if(eb) eb.className = BASE + (name==='equal'          ? ' bg-blue-600 text-white border-blue-600' : ' bg-slate-100 text-slate-600 border-slate-200');
    if(wb) wb.className = BASE + (name==='strong_weekend' ? ' bg-rose-600 text-white border-rose-600' : ' bg-slate-100 text-slate-600 border-slate-200');
}

function applyPreset(name) {
    if(name === 'equal')         { applyDayRatio(50, 50); }  // m\u1ed7i ng\u00e0y b\u1eb1ng nhau
    if(name === 'strong_weekend'){ applyDayRatio(40, 60); }  // cu\u1ed1i tu\u1ea7n m\u1ea1nh h\u01a1n
    setPresetActive(name);
    recalc();
}

// Ki\u1ec3m tra ca: t\u00f4 \u0111\u1ecf t\u1eebng input n\u1ebfu t\u1ed5ng \u2260 100
function checkShift(cls){
    const inputs = Array.from(document.querySelectorAll(`.${cls}-shift`));
    const sum = inputs.reduce((s,i)=>s+(parseFloat(i.value)||0),0);
    const ok = Math.abs(sum-100)<0.15;
    const badge = document.getElementById(`${cls}_sum_badge`);
    badge.textContent = sum.toFixed(2)+'%';
    badge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full '
        +(ok ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-100 text-rose-600 animate-pulse');
    inputs.forEach(i => {
        i.style.borderColor     = ok ? '' : '#fb7185';
        i.style.backgroundColor = ok ? '' : '#fff1f2';
        i.style.color           = ok ? '' : '#be123c';
        i.style.outline         = ok ? '' : '2px solid #fda4af';
    });
}

// T\u1ef1 \u0111\u1ed9ng ph\u00e2n b\u1ed5 % tu\u1ea7n theo s\u1ed1 ng\u00e0y th\u1ef1c t\u1ebf
function autoDistributeWeeks() {
    const rows = document.querySelectorAll('tr[data-week-row]');
    let dayCounts = [], totalDays = 0;
    rows.forEach(row => {
        const pDays = row.dataset.days ? row.dataset.days.split(',').filter(Boolean) : [];
        dayCounts.push(pDays.length);
        totalDays += pDays.length;
    });
    if(totalDays === 0) return;
    const inputs = document.querySelectorAll('input.week-w');
    let assigned = 0;
    inputs.forEach((inp, idx) => {
        if(idx < inputs.length - 1) {
            const pct = parseFloat((dayCounts[idx] / totalDays * 100).toFixed(2));
            inp.value = pct.toFixed(2);
            assigned += pct;
        } else {
            // S\u1ed1 d\u01b0 v\u00e0o tu\u1ea7n cu\u1ed1i \u0111\u1ec3 \u0111\u1ea3m b\u1ea3o t\u1ed5ng = 100%
            inp.value = parseFloat((100 - assigned).toFixed(2));
        }
    });
    recalc();
}

function recalc(){
    TOTAL = parseInt(document.getElementById('inp_total').value) || 0;
    document.getElementById('total_fmt').textContent = TOTAL.toLocaleString('vi-VN');
    const ww = getWW();
    const dw = getDW();
    const wSum = ww.reduce((a,b)=>a+b,0);
    const wOk = Math.abs(wSum-100)<0.15;

    // Badge t\u1ed5ng tu\u1ea7n
    const wb2 = document.getElementById('wk_badge');
    wb2.textContent = 'T\u1ed5ng: '+wSum.toFixed(2)+'%';
    wb2.style.color = wOk ? '#6ee7b7' : '#fca5a5';
    wb2.style.animation = wOk ? '' : 'pulse 1s infinite';

    // T\u00f4 \u0111\u1ecf inputs tu\u1ea7n khi t\u1ed5ng \u2260 100%
    document.querySelectorAll('input.week-w').forEach(inp => {
        inp.style.borderColor = wOk ? '' : '#fb7185';
        inp.style.backgroundColor = wOk ? '' : '#fff1f2';
    });

    // day_sum_badge: hi\u1ec3n t\u1ef7 l\u1ec7 ng\u00e0y
    const db = document.getElementById('day_sum_badge');
    const eu = dw[1]||1, lu = dw[5]||1;
    db.textContent = `\u0110\u01a1n v\u1ecb: ${parseFloat(eu).toFixed(2)}:${parseFloat(lu).toFixed(2)}`;
    db.className = 'font-black text-emerald-300';

    for(let w=1;w<=5;w++){
        const wAmt = TOTAL*(ww[w-1]||0)/100;
        const wc = document.querySelector(`.week-amt[data-w="${w}"]`);
        if(wc) wc.textContent = Math.round(wAmt).toLocaleString('vi-VN');

        const row = document.querySelector(`tr[data-week-row="${w}"]`);
        if(!row) continue;
        const pDays = row.dataset.days ? row.dataset.days.split(',').filter(Boolean).map(Number) : [];

        // wDS = t\u1ed5ng \u0111\u01a1n v\u1ecb c\u00e1c ng\u00e0y C\u00d3 M\u1eb6T trong tu\u1ea7n
        // V\u00ed d\u1ee5: tu\u1ea7n 5 c\u00f3 T2,T3,T4,T5,T6 v\u00e0 t\u1ef7 l\u1ec7 4:6 → wDS = 4+4+4+4+6 = 22
        const wDS = pDays.reduce((s,d) => s+(dw[d]||1), 0);

        // % hi\u1ec7u qu\u1ea3 trong tu\u1ea7n (t\u1ed5ng lu\u00f4n = 100%)
        row.querySelectorAll('.day-pct').forEach(s => {
            const dow = parseInt(s.dataset.dow);
            if(!pDays.includes(dow)) return;
            const effPct = wDS > 0 ? (dw[dow]||1) / wDS * 100 : 0;
            s.textContent = effPct.toFixed(2)+'%';
        });

        // Ti\u1ec1n t\u1eebng ng\u00e0y = KPI_tu\u1ea7n × \u0111\u01a1n_v\u1ecb(d) / wDS
        document.querySelectorAll(`.day-kpi[data-w="${w}"]`).forEach(cell => {
            const dow = parseInt(cell.dataset.dow);
            if(!pDays.includes(dow)) return;
            const dAmt = wDS > 0 ? wAmt * (dw[dow]||1) / wDS : 0;
            cell.textContent = Math.round(dAmt).toLocaleString('vi-VN');
        });
    }
}

function validateForm(){
    const ww=getWW(), ws=ww.reduce((a,b)=>a+b,0);
    if(Math.abs(ws-100)>0.5){ alert('T\u1ed5ng % tu\u1ea7n ph\u1ea3i = 100%! Hi\u1ec7n: '+ws.toFixed(2)+'%'); return false; }
    const eu = parseInt(document.getElementById('early_unit').value)||0;
    const lu = parseInt(document.getElementById('late_unit').value)||0;
    if(eu<1||lu<1){ alert('\u0110\u01a1n v\u1ecb ng\u00e0y ph\u1ea3i \u2265 1!'); return false; }
    return true;
}
recalc(); checkShift('wd'); checkShift('we');
</script>
@endsection

