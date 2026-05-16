@extends('layouts.app')

@section('title', 'Báo cáo Nhân sự & KPI - KRIK')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Bộ lọc -->
        <div class="md:col-span-1 space-y-4">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ngày báo cáo</label>
                <input type="date" name="date" class="w-full px-4 py-2 rounded-lg border-2 border-slate-100 outline-none font-bold text-slate-700" value="{{ $date }}" onchange="window.location.href='?date='+this.value+'&store_id={{ $storeId }}'">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cửa hàng</label>
                <select name="store_id" class="w-full px-4 py-2 rounded-lg border-2 border-slate-100 outline-none font-bold text-slate-700" onchange="window.location.href='?date={{ $date }}&store_id='+this.value">
                    <option value="">-- Chọn cửa hàng --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->code }} - {{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Chỉ số tổng (Giống trên cùng của Sheet) -->
        <div class="md:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100">
                <p class="text-[9px] font-bold text-emerald-600 uppercase">Tổng Doanh Thu CH</p>
                <input type="number" id="total_revenue_input" class="w-full bg-transparent border-none p-0 text-lg font-black text-emerald-700 outline-none" onblur="equalizeKPI()" placeholder="0">
            </div>
            <div class="bg-blue-50 p-3 rounded-xl border border-blue-100">
                <p class="text-[9px] font-bold text-blue-600 uppercase">Số lượng khách</p>
                <div class="text-lg font-black text-blue-700">{{ $users->sum(fn($u) => $u->shifts instanceof \Illuminate\Support\Collection ? $u->shifts->sum('customers') : 0) }}</div>
            </div>
            <div class="bg-amber-50 p-3 rounded-xl border border-amber-100">
                <p class="text-[9px] font-bold text-amber-600 uppercase">Số hóa đơn</p>
                <div class="text-lg font-black text-amber-700">{{ $users->sum(fn($u) => $u->shifts instanceof \Illuminate\Support\Collection ? $u->shifts->sum('orders') : 0) }}</div>
            </div>
            <div class="bg-purple-50 p-3 rounded-xl border border-purple-100">
                <p class="text-[9px] font-bold text-purple-600 uppercase">Số lượng sản phẩm</p>
                <div class="text-lg font-black text-purple-700">{{ $users->sum(fn($u) => $u->shifts instanceof \Illuminate\Support\Collection ? $u->shifts->sum('products') : 0) }}</div>
            </div>
        </div>
    </div>
</div>

@if($storeId)
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse table-compact min-w-[1400px]">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th rowspan="2" class="px-4 py-4 border-r border-slate-700 sticky left-0 bg-slate-800 z-20">Họ và tên / Chức danh</th>
                    <th colspan="3" class="px-4 py-2 text-center border-r border-slate-700 bg-blue-600">Giờ công</th>
                    <th colspan="3" class="px-4 py-2 text-center border-r border-slate-700 bg-emerald-600">Doanh thu cá nhân</th>
                    <th colspan="4" class="px-4 py-2 text-center border-r border-slate-700 bg-slate-600">Kết quả chi tiết</th>
                    <th rowspan="2" class="px-4 py-4 text-right bg-amber-500 text-white">KPI Cá nhân</th>
                    <th colspan="3" class="px-4 py-2 text-center bg-rose-600">Hiệu suất cá nhân</th>
                </tr>
                <tr class="bg-slate-700">
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-16 text-[8px]">Sáng</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-16 text-[8px]">Chiều</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-16 text-[8px]">Tối</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-24 text-[8px]">DT Sáng</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-24 text-[8px]">DT Chiều</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-24 text-[8px]">DT Tối</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-12 text-[8px]">KH</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-12 text-[8px]">Thử</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-12 text-[8px]">Đơn</th>
                    <th class="px-2 py-2 text-center border-r border-slate-600 w-12 text-[8px]">SP</th>
                    <th class="px-2 py-2 text-right border-r border-rose-500 w-28 text-[8px]">Tổng DTCN</th>
                    <th class="px-2 py-2 text-center border-r border-rose-500 w-20 text-[8px]">SP/BILL</th>
                    <th class="px-2 py-2 text-center w-24 text-[8px]">VND/BILL</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 border-r border-slate-100 sticky left-0 bg-white z-10">
                        <div class="font-bold text-slate-800 text-xs">{{ $user->full_name }}</div>
                        <div class="text-[8px] text-slate-400 font-bold uppercase">{{ $user->position->code ?? 'STAFF' }}</div>
                    </td>
                    
                    <!-- Giờ công -->
                    @foreach(['morning', 'afternoon', 'evening'] as $shift)
                    <td class="px-1 py-2 border-r border-slate-100">
                        <input type="number" step="0.5" class="w-full bg-transparent text-center text-xs font-bold focus:bg-blue-50 outline-none" 
                            value="{{ $user->shifts[$shift]->hours ?? '' }}" onblur="updateField({{ $user->id }}, '{{ $shift }}', 'hours', this.value)">
                    </td>
                    @endforeach

                    <!-- Doanh thu cá nhân -->
                    @foreach(['morning', 'afternoon', 'evening'] as $shift)
                    <td class="px-1 py-2 border-r border-slate-100">
                        <input type="number" class="w-full bg-transparent text-right text-[10px] font-bold focus:bg-emerald-50 outline-none" 
                            value="{{ isset($user->shifts[$shift]) ? round($user->shifts[$shift]->personal_revenue) : '' }}" onblur="updateField({{ $user->id }}, '{{ $shift }}', 'personal_revenue', this.value)">
                    </td>
                    @endforeach

                    <!-- Kết quả chi tiết (Lấy từ ca sáng làm đại diện hoặc tổng) -->
                    @foreach(['customers', 'fitting_rooms', 'orders', 'products'] as $field)
                    <td class="px-1 py-2 border-r border-slate-100 text-center">
                        <input type="number" class="w-full bg-transparent text-center text-[10px] focus:bg-slate-100 outline-none" 
                            value="{{ $user->shifts->first()->$field ?? 0 }}" onblur="updateField({{ $user->id }}, 'morning', '{{ $field }}', this.value)">
                    </td>
                    @endforeach

                    <!-- KPI Cá nhân -->
                    <td class="px-4 py-3 text-right bg-amber-50 border-r border-amber-100">
                        @php 
                            $totalRev = $user->shifts->sum('personal_revenue');
                            $target = $user->shifts->first()->target_amount ?? 0;
                            $pct = ($target > 0) ? ($totalRev / $target * 100) : 0;
                        @endphp
                        <div class="font-bold text-slate-800 text-xs">{{ round($pct, 1) }}%</div>
                        <div class="text-[8px] text-slate-400 font-mono">T: {{ number_format($target/1000, 0) }}k</div>
                    </td>

                    <!-- Hiệu suất -->
                    <td class="px-4 py-3 text-right font-bold text-rose-600 text-xs border-r border-slate-100">
                        {{ number_format($totalRev, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600 text-[10px] border-r border-slate-100">
                        @php $totalProducts = $user->shifts->sum('products'); $totalOrders = $user->shifts->sum('orders'); @endphp
                        {{ $totalOrders > 0 ? round($totalProducts / $totalOrders, 1) : 0 }}
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600 text-[10px]">
                        {{ $totalOrders > 0 ? number_format($totalRev / $totalOrders, 0, ',', '.') : 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function updateField(userId, shiftType, field, value) {
        fetch('{{ route("fe.daily.update") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ user_id: userId, store_id: '{{ $storeId }}', date: '{{ $date }}', shift_type: shiftType, field: field, value: value || 0 })
        }).then(() => { if(field === 'personal_revenue' || field === 'hours') location.reload(); });
    }

    function equalizeKPI() {
        const totalRev = document.getElementById('total_revenue_input').value;
        if (!totalRev) return;
        fetch('{{ route("fe.daily.equalize") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ date: '{{ $date }}', store_id: '{{ $storeId }}', total_revenue: totalRev })
        }).then(() => location.reload());
    }
</script>
<style>
    .table-compact input::-webkit-outer-spin-button, .table-compact input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
@endsection
