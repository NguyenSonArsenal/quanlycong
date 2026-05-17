@extends('layouts.app')
@section('title', 'Cấu hình KPI')
@section('has_local_alert', true)
@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-lg font-black text-slate-800 tracking-tight">Cấu hình KPI</h1>
        <p class="text-[11px] text-slate-400 mt-0.5">Quản lý mục tiêu doanh thu theo cửa hàng & tháng</p>
    </div>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')"
        class="flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-xl font-black text-sm shadow-lg shadow-rose-100 transition-all active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Thêm KPI mới
    </button>
</div>

@if(session('success'))<div class="mb-3 bg-emerald-50 border-l-4 border-emerald-400 text-emerald-700 text-xs px-4 py-2.5 rounded-r-xl font-medium">✅ {{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-3 bg-rose-50 border-l-4 border-rose-400 text-rose-700 text-xs px-4 py-2.5 rounded-r-xl font-medium">❌ {{ session('error') }}</div>@endif

{{-- Filter bar --}}
<div class="bg-white rounded-xl border border-slate-100 shadow-sm px-4 py-3 mb-4 flex flex-wrap gap-3 items-center">
    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Bộ lọc:</span>
    <select id="filter_store" onchange="applyFilter()" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 outline-none focus:border-rose-400 bg-slate-50">
        <option value="">Tất cả cửa hàng</option>
        @foreach($stores as $s)
        <option value="{{ $s->id }}" {{ request('store_id')==$s->id?'selected':'' }}>{{ $s->code }} — {{ $s->name }}</option>
        @endforeach
    </select>
    <select id="filter_year" onchange="applyFilter()" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 outline-none focus:border-rose-400 bg-slate-50">
        <option value="">Tất cả năm</option>
        @foreach($years as $y)
        <option value="{{ $y }}" {{ request('year')==$y?'selected':'' }}>{{ $y }}</option>
        @endforeach
    </select>
    <select id="filter_month" onchange="applyFilter()" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 outline-none focus:border-rose-400 bg-slate-50">
        <option value="">Tất cả tháng</option>
        @for($m=1;$m<=12;$m++)
        <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}" {{ request('month')==str_pad($m,2,'0',STR_PAD_LEFT)?'selected':'' }}>Tháng {{ $m }}</option>
        @endfor
    </select>
    <span class="text-[11px] text-slate-400 ml-auto">{{ $configs->total() }} kết quả</span>
</div>

{{-- Grid Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($configs as $cfg)
    @php
        $dr    = $cfg->daily_ratios ?? [];
        $eu = (float)($dr[1] ?? 50);
        $lu = (float)($dr[5] ?? 50);
        $sum = ($eu + $lu) ?: 100;
        $early = round($eu / $sum * 100, 1);
        $late  = round(100 - $early, 1);
        $totalDays = $cfg->dailyTargets->count();
        $monthLabel = \Carbon\Carbon::parse($cfg->month.'-01')->locale('vi')->isoFormat('MMMM [năm] Y');
    @endphp
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-rose-200 transition-all group">
        {{-- Card header --}}
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-500 flex items-center justify-center shadow-sm shadow-rose-200">
                    <span class="text-white font-black text-xs">{{ $cfg->store->code }}</span>
                </div>
                <div>
                    <p class="font-black text-slate-800 text-sm leading-tight">{{ $cfg->store->name }}</p>
                    <p class="text-[10px] text-slate-400 font-medium capitalize">{{ $monthLabel }}</p>
                </div>
            </div>
            <span class="text-[9px] font-black px-2 py-1 rounded-full {{ $totalDays>0 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                {{ $totalDays>0 ? '✓ Đã cấu hình' : '⚠ Chưa có targets' }}
            </span>
        </div>

        {{-- Card body --}}
        <div class="px-5 py-3">
            <div class="mb-3">
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1">Tổng KPI tháng</p>
                <p class="text-xl font-black text-emerald-600 tracking-tight">{{ number_format($cfg->total_target,0,',','.') }}<span class="text-sm font-bold text-slate-400 ml-0.5">đ</span></p>
            </div>
            <div class="flex gap-4">
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">T2–T5</p>
                    <p class="text-xs font-black text-blue-600">{{ $early }}%</p>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">T6–CN</p>
                    <p class="text-xs font-black text-rose-500">{{ $late }}%</p>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Tuần</p>
                    <p class="text-xs font-black text-slate-600">{{ count($cfg->weekly_ratios ?? []) }} tuần</p>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ngày</p>
                    <p class="text-xs font-black text-slate-600">{{ $totalDays }} ngày</p>
                </div>
            </div>
        </div>

        {{-- Card actions --}}
        <div class="px-5 py-3 border-t border-slate-50 flex gap-2">
            <a href="{{ route('fe.kpi-config.show', $cfg->id) }}"
                class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-[11px] font-black text-center transition-all">
                📊 Xem chi tiết
            </a>
            <button type="button"
                onclick="openEdit({{ $cfg->id }},'{{ $cfg->store_id }}','{{ $cfg->month }}',{{ $cfg->total_target }})"
                class="px-4 py-2 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50 text-slate-500 hover:text-blue-600 text-[11px] font-black transition-all">
                ✏ Sửa
            </button>
            <form action="{{ route('fe.kpi-config.destroy', $cfg->id) }}" method="POST"
                onsubmit="return confirm('Xoá cấu hình KPI {{ $cfg->store->code }} / {{ $cfg->month }}?\nToàn bộ daily targets sẽ bị xoá vĩnh viễn!')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-rose-300 hover:bg-rose-50 text-slate-400 hover:text-rose-500 text-[11px] font-black transition-all">🗑</button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-3 py-16 text-center text-slate-400">
        <div class="text-5xl mb-3 opacity-30">📊</div>
        <p class="font-bold text-sm">Chưa có cấu hình KPI nào</p>
        <p class="text-xs mt-1">Nhấn "Thêm KPI mới" để bắt đầu</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($configs->hasPages())
<div class="mt-4">{{ $configs->appends(request()->query())->links() }}</div>
@endif

{{-- ═══ MODAL TẠO MỚI ═══ --}}
<div id="createModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100 animate-fade-in">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-800 text-base">🆕 Thêm KPI mới</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Chọn cửa hàng, tháng và tổng mục tiêu</p>
            </div>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-black leading-none">×</button>
        </div>
        <form action="{{ route('fe.kpi-config.store') }}" method="POST" class="px-6 py-5 space-y-4">@csrf
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Cửa hàng</label>
                <select name="store_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-rose-400 font-bold bg-slate-50">
                    @foreach($stores as $s)<option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Tháng cấu hình</label>
                <input type="month" name="month" value="{{ date('Y-m') }}" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-rose-400 font-bold bg-slate-50">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Tổng doanh thu mục tiêu (đ)</label>
                <input type="text" id="create_total_display" placeholder="Ví dụ: 1.000.000.000" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-rose-400 font-black bg-slate-50 text-emerald-600" oninput="formatNumberInput(this, 'create_total')">
                <input type="hidden" name="total_target" id="create_total">
                <p class="text-[10px] text-slate-400 mt-1">💡 Nhập từ 1 - 10 tỷ (Tự động thêm dấu chấm phân cách)</p>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-black hover:bg-slate-50">Hủy</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-black shadow-lg shadow-rose-100 transition-all">Tạo & Cấu hình →</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ MODAL EDIT ═══ --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-800 text-base">✏ Cập nhật KPI</h3>
                <p class="text-[10px] text-slate-400 mt-0.5" id="editModalSub">—</p>
            </div>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-black leading-none">×</button>
        </div>
        <form id="editForm" method="POST" class="px-6 py-5 space-y-4">@csrf @method('PATCH')
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Cửa hàng</label>
                <select name="store_id" id="edit_store_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-400 font-bold bg-slate-50">
                    @foreach($stores as $s)<option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Tháng</label>
                <input type="month" name="month" id="edit_month" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-400 font-bold bg-slate-50">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-500 uppercase mb-1.5 block">Tổng KPI tháng (đ)</label>
                <input type="text" id="edit_total_display" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-400 font-black bg-slate-50 text-emerald-600" oninput="formatNumberInput(this, 'edit_total')">
                <input type="hidden" name="total_target" id="edit_total">
                <p class="text-[10px] text-slate-400 mt-1">💡 Nhập từ 1 - 10 tỷ (Tự động thêm dấu chấm phân cách)</p>
                <p class="text-[10px] text-amber-600 mt-1">⚠ Lưu sẽ tự tính lại toàn bộ daily targets theo cấu hình hiện tại.</p>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-black hover:bg-slate-50">Hủy</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-black shadow-lg shadow-blue-100 transition-all">💾 Lưu cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function formatNumberInput(input, rawInputId) {
    // 1. Remove all non-digits
    let value = input.value.replace(/\D/g, '');
    
    // 2. Parse as integer
    let num = parseInt(value, 10);
    
    // 3. Cap at 10 billion (10,000,000,000)
    if (num > 10000000000) {
        num = 10000000000;
    }
    
    // 4. Update inputs
    const rawInput = document.getElementById(rawInputId);
    if (isNaN(num)) {
        input.value = '';
        if (rawInput) rawInput.value = '';
    } else {
        // Format with dot separators
        input.value = num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        if (rawInput) rawInput.value = num;
    }
}

function applyFilter() {
    const s = document.getElementById('filter_store').value;
    const y = document.getElementById('filter_year').value;
    const m = document.getElementById('filter_month').value;
    const params = new URLSearchParams();
    if(s) params.set('store_id', s);
    if(y) params.set('year', y);
    if(m) params.set('month', m);
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function openEdit(id, storeId, month, total) {
    document.getElementById('editForm').action = '/staff-shift-kpi/kpi-config/' + id;
    document.getElementById('edit_store_id').value = storeId;
    document.getElementById('edit_month').value = month;
    
    // Set raw value
    const rawInput = document.getElementById('edit_total');
    rawInput.value = total;
    
    // Set formatted value
    const displayInput = document.getElementById('edit_total_display');
    displayInput.value = total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    document.getElementById('editModalSub').textContent = 'Cấu hình #' + id + ' — ' + month;
    document.getElementById('editModal').classList.remove('hidden');
}

document.getElementById('createModal').addEventListener('click', function(e){ if(e.target===this) this.classList.add('hidden'); });
document.getElementById('editModal').addEventListener('click', function(e){ if(e.target===this) this.classList.add('hidden'); });
</script>
@endsection
