@extends('layouts.app')
@section('title', 'Quản lý KPI')
@section('content')
@php
    $dr  = []; foreach(($dailyRatios??$config->daily_ratios??[]) as $k=>$v) { $dr[(int)$k]=(float)$v; }
    $wr  = []; foreach(($weeklyRatios??$config->weekly_ratios??[]) as $k=>$v) { $wr[(int)$k]=(float)$v; }
    $swd = $config->shift_ratios_weekday ?? ['morning'=>10,'afternoon'=>36,'evening'=>54];
    $swe = $config->shift_ratios_weekend ?? ['morning'=>12,'afternoon'=>45,'evening'=>43];
    $total = $config->total_target;
    for($i=1;$i<=7;$i++) if(!isset($dr[$i])) $dr[$i]=round(100/7,2);
    for($i=1;$i<=5;$i++) if(!isset($wr[$i])) $wr[$i]=20.0;
@endphp

{{-- Month switcher --}}
<div class="flex items-center justify-between mb-3 flex-wrap gap-2">
    <div class="flex flex-wrap gap-1.5 items-center">
        <span class="text-xs font-black text-slate-500 mr-1">📊 KPI:</span>
        @foreach($configs as $c)
        <a href="{{ route('fe.kpi-config.show', $c->id) }}"
           class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all
                  {{ $c->id===$config->id ? 'bg-rose-500 text-white border-rose-500 shadow' : 'bg-white text-slate-500 border-slate-200 hover:border-rose-300' }}">
            {{ $c->store->code }} / {{ $c->month }}
        </a>
        @endforeach
        <button onclick="document.getElementById('createModal').classList.remove('hidden')"
            class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-dashed border-slate-300 text-slate-400 hover:border-rose-400 hover:text-rose-500">
            + Tạo mới
        </button>
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
                <input type="number" name="total_target" id="inp_total" value="{{ $total }}" min="1" step="1"
                    class="flex-1 px-3 py-2 rounded-lg border border-emerald-200 focus:border-emerald-400 outline-none font-black text-emerald-700 text-sm" oninput="recalc()">
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
                        class="we-shift w-full px-1 py-1 rounded border border-slate-200 font-bold text-indigo-700 text-center text-xs outline-none" oninput="checkShift('we')">
                </div>
                @endforeach
            </div>
        </div>
        {{-- Phân bổ --}}
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Phân bổ T2–T5 / T6–CN</p>
            <div class="flex gap-2 mb-2">
                <div class="flex-1">
                    <input type="number" id="early_pct" value="57.14" step="0.01" class="w-full px-2 py-1 rounded border border-blue-200 font-bold text-blue-600 text-center text-xs outline-none" oninput="onEarlyChange()">
                </div>
                <div class="flex-1">
                    <input type="number" id="late_pct" value="42.86" step="0.01" class="w-full px-2 py-1 rounded border border-rose-200 font-bold text-rose-600 text-center text-xs outline-none" oninput="onLateChange()">
                </div>
            </div>
            <div class="flex gap-1">
                <button type="button" onclick="applyPreset('equal')" class="flex-1 py-1 rounded bg-slate-100 text-[9px] font-bold border border-slate-200">Bằng nhau</button>
                <button type="button" onclick="applyPreset('strong_weekend')" class="flex-1 py-1 rounded bg-slate-100 text-[9px] font-bold border border-slate-200">Cuối tuần mạnh</button>
            </div>
            <p class="text-[9px] text-center mt-1"><span id="early_late_badge" class="font-bold text-emerald-600">Tổng: 100%</span></p>
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
            KPI Tuần<br><span id="wk_badge" class="text-[9px] font-normal text-emerald-300">Tổng: 100%</span>
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
        <td class="px-2 py-2 border-r border-slate-100 text-center min-w-[100px]">
            <div class="flex flex-col items-center gap-1">
                <div class="flex items-center gap-1">
                    <input type="number" name="week_weights[{{ $w }}]" value="{{ number_format($weekWt,2,'.','') }}" step="0.01" min="0" max="100"
                        class="week-w w-14 px-1 py-0.5 rounded border border-blue-200 font-black text-blue-700 text-center text-[11px] outline-none" oninput="recalc()">
                    <span class="text-[9px] font-bold text-slate-400">%</span>
                </div>
                <div class="week-amt font-black text-emerald-700 text-[11px]" data-w="{{ $w }}">{{ number_format($weekAmt,0,',','.') }}</div>
            </div>
        </td>
        @foreach([1,2,3,4,5,6,7] as $d)
        @php $isWE = $d>=5; @endphp
        <td class="px-1 py-2 border-r border-slate-100 text-center {{ $isWE?'bg-rose-50/20':'bg-blue-50/20' }} {{ $d==7?'border-r-0':'' }} min-w-[85px]">
            <div class="flex flex-col items-center gap-1.5 py-1">
                @if(in_array($d, $presentDows))
                    <span class="text-[9px] {{ $isWE?'text-rose-500':'text-blue-500' }} font-black uppercase tracking-tighter">{{ $dateMap[$d] }}</span>
                    @if($w===1)
                    <div class="relative w-16">
                        <input type="number" name="day_weights[{{ $d }}]" value="{{ number_format($dr[$d]??14.28,2,'.','') }}" step="0.01" min="0" max="100"
                            class="day-w w-full px-1 py-0.5 rounded border {{ $isWE?'border-rose-200 focus:border-rose-500':'border-blue-200 focus:border-blue-500' }} outline-none font-black {{ $isWE?'text-rose-600':'text-blue-600' }} text-center text-[11px] shadow-sm"
                            data-dow="{{ $d }}" oninput="syncEarlyLate();recalc()">
                        <span class="absolute -right-1 top-0.5 text-[8px] opacity-40 font-bold">%</span>
                    </div>
                    @else
                    <span class="font-black {{ $isWE?'text-rose-600':'text-blue-600' }} day-pct text-[11px] bg-white/50 px-2 py-0.5 rounded border {{ $isWE?'border-rose-100':'border-blue-100' }} shadow-sm" data-dow="{{ $d }}">{{ number_format($dr[$d]??14.28,2) }}%</span>
                    @endif
                    <div class="day-kpi text-[12px] font-mono font-black {{ $isWE?'text-rose-900':'text-blue-900' }} mt-0.5 tracking-tight" data-w="{{ $w }}" data-dow="{{ $d }}" data-present="1">
                        {{ number_format(round($byDow[$d]??0),0,',','.') }}
                    </div>
                @else
                    {{-- Giữ input ẩn để JS getDW() luôn lấy được đủ 7 ngày của tháng --}}
                    @if($w===1)
                    <input type="number" name="day_weights[{{ $d }}]" value="{{ number_format($dr[$d]??14.28,2,'.','') }}" class="day-w hidden" data-dow="{{ $d }}">
                    @endif
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
    <p class="text-[10px] text-slate-400 italic">💡 Gộp [Tỷ trọng %] & [Số tiền] vào chung cột để tiết kiệm diện tích. Hệ thống tự ẩn các ngày không thuộc tháng.</p>
    <button type="submit" onclick="return validateForm()" class="bg-rose-500 text-white px-10 py-3 rounded-xl font-black text-sm hover:bg-rose-600 shadow-lg active:scale-95 transition-all uppercase tracking-tight">✓ LƯU CẤU HÌNH KPI</button>
</div>
</form>

<script>
let TOTAL = {{ $total }};
function getDW(){ const o={}; document.querySelectorAll('input.day-w').forEach(i=>{ o[i.dataset.dow]=parseFloat(i.value)||0; }); return o; }
function getWW(){ return Array.from(document.querySelectorAll('input.week-w')).map(i=>parseFloat(i.value)||0); }

function distribute(total, count) {
    const base = (total / count);
    return Array.from({length: count}, () => parseFloat(base.toFixed(2)));
}

function applyDayWeights(earlyPct, latePct) {
    const earlyDays = distribute(earlyPct, 4); const lateDays = distribute(latePct, 3);
    [1,2,3,4].forEach((dow,i) => { const inp = document.querySelector(`input.day-w[data-dow="${dow}"]`); if(inp) inp.value = earlyDays[i]; });
    [5,6,7].forEach((dow,i) => { const inp = document.querySelector(`input.day-w[data-dow="${dow}"]`); if(inp) inp.value = lateDays[i]; });
    document.querySelectorAll('.day-pct').forEach(s => { const dw = getDW(); s.textContent = (dw[s.dataset.dow]||0).toFixed(2) + '%'; });
}

function syncEarlyLate() {
    const dw = getDW();
    const earlySum = [1,2,3,4].reduce((s,d) => s+(dw[d]||0), 0);
    const lateSum  = [5,6,7].reduce((s,d)   => s+(dw[d]||0), 0);
    document.getElementById('early_pct').value = earlySum.toFixed(2);
    document.getElementById('late_pct').value  = lateSum.toFixed(2);
    const tot = earlySum + lateSum;
    const el = document.getElementById('early_late_badge');
    el.textContent = `T2-T5: ${earlySum.toFixed(2)}% | T6-CN: ${lateSum.toFixed(2)}% | Tổng: ${tot.toFixed(2)}%`;
    el.className = 'font-bold '+(Math.abs(tot-100)<0.1 ? 'text-emerald-600' : 'text-rose-500 animate-pulse');
}

function onEarlyChange() { let ep=parseFloat(document.getElementById('early_pct').value)||0; if(ep>100)ep=100; const lp=100-ep; document.getElementById('late_pct').value=lp.toFixed(2); applyDayWeights(ep,lp); recalc(); }
function onLateChange() { let lp=parseFloat(document.getElementById('late_pct').value)||0; if(lp>100)lp=100; const ep=100-lp; document.getElementById('early_pct').value=ep.toFixed(2); applyDayWeights(ep,lp); recalc(); }
function applyPreset(name) {
    if(name==='equal'){ const e=(4/7*100).toFixed(2); const l=(100-e).toFixed(2); applyDayWeights(parseFloat(e),parseFloat(l)); document.getElementById('early_pct').value=e; document.getElementById('late_pct').value=l; }
    else { applyDayWeights(40,60); document.getElementById('early_pct').value=40; document.getElementById('late_pct').value=60; }
    syncEarlyLate(); recalc();
}

function checkShift(cls){
    const inputs = document.querySelectorAll(`.${cls}-shift`);
    const sum = Array.from(inputs).reduce((s,i)=>s+(parseFloat(i.value)||0),0);
    const badge = document.getElementById(`${cls}_sum_badge`);
    badge.textContent = sum.toFixed(2)+'%';
    badge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full '+(Math.abs(sum-100)<0.1?'bg-emerald-50 text-emerald-600':'bg-rose-50 text-rose-500 animate-pulse');
}

function recalc(){
    TOTAL = parseFloat(document.getElementById('inp_total').value)||0;
    document.getElementById('total_fmt').textContent = Math.round(TOTAL).toLocaleString('vi-VN');
    document.getElementById('total_sum_display').textContent = Math.round(TOTAL).toLocaleString('vi-VN');
    const ww = getWW(); const dw = getDW();
    const wSum = ww.reduce((a,b)=>a+b,0); const dSum = Object.values(dw).reduce((a,b)=>a+b,0);
    document.getElementById('wk_sum').textContent = wSum.toFixed(2)+'%';
    const wb = document.getElementById('wk_badge'); wb.textContent = 'Tổng: '+wSum.toFixed(2)+'%'; wb.className = 'text-[9px] font-normal '+(Math.abs(wSum-100)<0.1?'text-emerald-300':'text-rose-300 animate-pulse');
    const db = document.getElementById('day_sum_badge'); db.textContent = '% ngày: '+dSum.toFixed(2)+'%'; db.className = 'font-black '+(Math.abs(dSum-100)<0.1?'text-emerald-300':'text-rose-300 animate-pulse');
    
    for(let w=1;w<=5;w++){
        const wAmt = TOTAL*(ww[w-1]||0)/100;
        const wc = document.querySelector(`.week-amt[data-w="${w}"]`); if(wc) wc.textContent = Math.round(wAmt).toLocaleString('vi-VN');
        const row = document.querySelector(`tr[data-week-row="${w}"]`);
        const pDays = row && row.dataset.days ? row.dataset.days.split(',').filter(Boolean).map(Number) : [1,2,3,4,5,6,7];
        const wDS = pDays.reduce((s,d)=>s+(parseFloat(dw[d])||0),0);
        if(w>1) row.querySelectorAll('.day-pct').forEach(s => { const dow=parseInt(s.dataset.dow); if(pDays.includes(dow)) s.textContent=(dw[dow]||0).toFixed(2)+'%'; });
        document.querySelectorAll(`.day-kpi[data-w="${w}"]`).forEach(cell=>{
            const dow=parseInt(cell.dataset.dow); if(!pDays.includes(dow)) return;
            const dAmt = wDS>0 ? wAmt*(parseFloat(dw[dow])||0)/wDS : 0;
            cell.textContent = dAmt>0 ? Math.round(dAmt).toLocaleString('vi-VN') : '0';
        });
    }
}

function validateForm(){
    const ww=getWW(), ws=ww.reduce((a,b)=>a+b,0); if(Math.abs(ws-100)>0.5){ alert('Tổng % tuần phải = 100%! Hiện: '+ws.toFixed(2)+'%'); return false; }
    const dw=getDW(), ds=Object.values(dw).reduce((a,b)=>a+b,0); if(Math.abs(ds-100)>0.5){ alert('Tổng % ngày phải = 100%! Hiện: '+ds.toFixed(2)+'%'); return false; }
    return true;
}
recalc(); syncEarlyLate(); checkShift('wd'); checkShift('we');
</script>
@endsection
