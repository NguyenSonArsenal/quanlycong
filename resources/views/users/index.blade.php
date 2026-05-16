@extends('layouts.app')

@section('title', 'Quản lý Nhân sự')

@section('content')
<!-- Form thêm mới -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
    <h3 class="text-base font-semibold text-slate-800 mb-6">Thêm nhân sự mới</h3>
    <form action="{{ route('fe.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @csrf
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Tên đăng nhập</label>
            <input type="text" name="username" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-rose-500" required>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Họ và tên</label>
            <input type="text" name="full_name" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-rose-500" required>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Mật khẩu</label>
            <input type="password" name="password" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-rose-500" required>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Vai trò</label>
            <select name="role" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none">
                <option value="staff">Nhân viên</option>
                <option value="store_manager">Quản lý cửa hàng</option>
                <option value="admin">Admin hệ thống</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Cửa hàng</label>
            <select name="store_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none">
                <option value="">-- Trống --</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}">{{ $s->code }} - {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Chức danh</label>
            <select name="position_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none">
                <option value="">-- Trống --</option>
                @foreach($positions as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Loại HĐ</label>
            <select name="contract_type" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none">
                <option value="CT">Chính thức (CT)</option>
                <option value="TV">Thời vụ (TV)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Lương/giờ (đ)</label>
            <input type="number" name="hourly_rate" class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none" value="25000" min="0">
        </div>
        <div class="md:col-span-4 text-right">
            <button type="submit" class="bg-slate-900 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-black transition-all shadow-lg shadow-slate-200 mt-2">
                Lưu nhân sự
            </button>
        </div>
    </form>
</div>

<!-- Bộ lọc & Danh sách -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Hàng lọc -->
    <div class="p-4 bg-slate-50 border-b">
        <form action="{{ route('fe.users.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc username..." class="w-full px-4 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-rose-500">
            </div>
            <div class="w-48">
                <select name="store_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 text-sm outline-none" onchange="this.form.submit()">
                    <option value="">-- Tất cả cửa hàng --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <select name="position_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 text-sm outline-none" onchange="this.form.submit()">
                    <option value="">-- Tất cả chức danh --</option>
                    @foreach($positions as $p)
                        <option value="{{ $p->id }}" {{ request('position_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('fe.users.index') }}" class="text-xs text-slate-400 hover:text-rose-500 font-bold underline">Xóa lọc</a>
        </form>
    </div>

    <table class="w-full text-left border-collapse table-compact">
        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-wider">
            <tr>
                <th class="">Họ tên / Username</th>
                <th class="">Cửa hàng</th>
                <th class="">Chức danh / Vai trò</th>
                <th class="">Lương giờ</th>
                <th class=" text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($users as $user)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="">
                    <div class="font-bold text-slate-800 text-sm">{{ $user->full_name }}</div>
                    <div class="text-[9px] text-slate-400 font-mono">@ {{ $user->username }}</div>
                </td>
                <td class=" font-medium text-slate-600 text-xs">
                    {{ $user->store->code ?? '---' }}
                </td>
                <td class="">
                    <div class="text-[11px] text-slate-700 font-medium">{{ $user->position->name ?? '---' }}</div>
                    <span class="text-[8px] px-1 py-0.5 rounded bg-slate-100 text-slate-500 font-bold uppercase">{{ $user->role }}</span>
                </td>
                <td class="font-mono text-slate-600 text-xs">
                    <form action="{{ route('fe.users.update', $user->id) }}" method="POST" class="flex items-center gap-1">
                        @csrf @method('PATCH')
                        <input type="number" name="hourly_rate" value="{{ $user->hourly_rate }}"
                            class="w-24 px-2 py-1 rounded border border-slate-200 outline-none text-xs font-mono focus:border-blue-300"
                            min="0">
                        <button type="submit" class="text-[9px] text-blue-500 hover:text-blue-700 font-bold">Lưu</button>
                    </form>
                </td>
                <td class=" text-right">
                    <form action="{{ route('fe.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Xóa nhân sự này?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-rose-400 hover:text-rose-600 text-[10px] font-bold underline">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
