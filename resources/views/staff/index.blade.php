@extends('layouts.app')
@section('title', 'Danh sách Nhân sự')
@section('has_local_alert', true)
@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium flex items-center gap-2">
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-medium">{{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-black text-slate-800">👥 Danh sách Nhân sự</h1>
        <p class="text-xs text-slate-400 mt-0.5">Quản lý toàn bộ nhân viên trong hệ thống</p>
    </div>
    <a href="{{ route('fe.users.create') }}"
        class="flex items-center gap-2 bg-slate-900 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-700 transition-all shadow-lg shadow-slate-200">
        <span class="text-base">+</span> Thêm nhân sự
    </a>
</div>

{{-- Filter bar --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
    <form method="GET" action="{{ route('fe.users.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tìm kiếm</label>
            <input type="text" name="q" value="{{ request('q') }}"
                placeholder="Tên hoặc username..."
                class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm focus:border-blue-300">
        </div>
        <div class="w-44">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Cửa hàng</label>
            <select name="store_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                <option value="">-- Tất cả --</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->code }} – {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Chức danh</label>
            <select name="position_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                <option value="">-- Tất cả --</option>
                @foreach($positions as $p)
                    <option value="{{ $p->id }}" {{ request('position_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Vai trò</label>
            <select name="role" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                <option value="">-- Tất cả --</option>
                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                <option value="store_manager" {{ request('role') === 'store_manager' ? 'selected' : '' }}>Quản lý CH</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div class="w-32">
            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Trạng thái</label>
            <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                <option value="">-- Tất cả --</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang làm</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nghỉ việc</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-bold hover:bg-slate-700 transition-all">Lọc</button>
            <a href="{{ route('fe.users.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-200 transition-all">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500 font-medium">Tổng: <strong class="text-slate-800">{{ $users->total() }}</strong> nhân sự</span>
        <span class="text-[10px] text-slate-400">Trang {{ $users->currentPage() }}/{{ $users->lastPage() }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Họ tên / Username</th>
                    <th class="px-3 py-2">Cửa hàng</th>
                    <th class="px-3 py-2">Chức danh</th>
                    <th class="px-3 py-2 text-center">Vai trò</th>
                    <th class="px-3 py-2 text-center">HĐ</th>
                    <th class="px-3 py-2 text-right">Lương/h</th>
                    <th class="px-3 py-2 text-center">Trạng thái</th>
                    <th class="px-3 py-2 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $i => $u)
                @php
                    $roleColors = ['admin'=>'bg-rose-100 text-rose-700','store_manager'=>'bg-blue-100 text-blue-700','staff'=>'bg-slate-100 text-slate-600'];
                    $roleLabels = ['admin'=>'Admin','store_manager'=>'QLCH','staff'=>'NV'];
                @endphp
                <tr class="{{ $loop->even ? 'bg-slate-50/40' : 'bg-white' }} hover:bg-blue-50/20 transition-colors">
                    <td class="px-3 py-2 text-slate-400 text-xs">{{ $users->firstItem() + $loop->index }}</td>
                    <td class="px-3 py-2">
                        <div class="font-bold text-slate-800">{{ $u->full_name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $u->username }}</div>
                    </td>
                    <td class="px-3 py-2 text-xs font-medium text-slate-600">
                        {{ $u->store ? $u->store->code . ' – ' . $u->store->name : '—' }}
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-600">{{ $u->position->name ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $roleColors[$u->role] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $roleLabels[$u->role] ?? $u->role }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $u->contract_type === 'TV' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $u->contract_type ?? 'CT' }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-right font-mono text-xs">
                        {{ $u->hourly_rate > 0 ? number_format($u->hourly_rate, 0, ',', '.') . 'đ' : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($u->status)
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-700">Đang làm</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-slate-100 text-slate-500">Nghỉ việc</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-2 flex-wrap">
                            @if(in_array(auth()->user()->role, ['admin','store_manager']))
                            <a href="{{ route('fe.profile', ['user_id' => $u->id, 'month' => date('Y-m')]) }}"
                                class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-bold hover:bg-emerald-200 transition-all">
                                💰 Lương
                            </a>
                            @endif
                            <a href="{{ route('fe.users.edit', $u->id) }}"
                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-bold hover:bg-blue-200 transition-all">
                                Sửa
                            </a>
                            @if($u->id !== auth()->id())
                            <form action="{{ route('fe.users.destroy', $u->id) }}" method="POST"
                                onsubmit="return confirm('Xóa nhân sự {{ $u->full_name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1 bg-rose-100 text-rose-600 rounded-lg text-[10px] font-bold hover:bg-rose-200 transition-all">
                                    Xóa
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                        <div class="text-2xl mb-2">👤</div>
                        <p class="font-medium">Không tìm thấy nhân sự nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">Hiển thị {{ $users->firstItem() }}–{{ $users->lastItem() }} / {{ $users->total() }}</span>
        <div class="flex gap-1">
            @if($users->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-xs text-slate-300 bg-slate-50">← Trước</span>
            @else
                <a href="{{ $users->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 font-medium">← Trước</a>
            @endif
            @foreach($users->getUrlRange(max(1,$users->currentPage()-2), min($users->lastPage(),$users->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $page == $users->currentPage() ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $page }}</a>
            @endforeach
            @if($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 font-medium">Sau →</a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs text-slate-300 bg-slate-50">Sau →</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
