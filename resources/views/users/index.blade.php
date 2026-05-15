@extends('layouts.app')

@section('title', 'Quản lý Nhân sự')

@section('content')
<div class="space-y-8">
    <!-- Form thêm mới -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="text-base font-semibold text-slate-800 mb-6">Thêm nhân sự mới</h3>
        <form action="{{ route('fe.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tên đăng nhập</label>
                <input type="text" name="username" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Họ và tên</label>
                <input type="text" name="full_name" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vai trò</label>
                <select name="role" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    <option value="staff">Nhân viên</option>
                    <option value="store_manager">Quản lý cửa hàng</option>
                    <option value="area_manager">Quản lý khu vực</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cửa hàng</label>
                <select name="store_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    <option value="">-- Trống --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->code }} - {{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chức danh</label>
                <select name="position_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    <option value="">-- Trống --</option>
                    @foreach($positions as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Loại HĐ</label>
                <select name="contract_type" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    <option value="CT">Chính thức (CT)</option>
                    <option value="TV">Thời vụ (TV)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lương/giờ</label>
                <input type="number" name="hourly_rate" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" value="25000">
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="bg-slate-900 text-white px-8 py-2.5 rounded-lg font-medium hover:bg-slate-800 transition-colors">
                    Lưu nhân sự
                </button>
            </div>
        </form>
    </div>

    <!-- Danh sách -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                <tr>
                    <th class="px-6 py-4">Họ tên / Username</th>
                    <th class="px-6 py-4">Cửa hàng</th>
                    <th class="px-6 py-4">Chức danh / Vai trò</th>
                    <th class="px-6 py-4">Lương giờ</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $u)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-slate-900">{{ $u->full_name }}</div>
                        <div class="text-xs text-slate-400">@ {{ $u->username }}</div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $u->store ? $u->store->code : '---' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-600">{{ $u->position ? $u->position->name : '---' }}</div>
                        <div class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded inline-block uppercase">{{ $u->role }}</div>
                    </td>
                    <td class="px-6 py-4 text-slate-600 font-medium">
                        {{ number_format($u->hourly_rate) }}đ
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($u->id !== Auth::id())
                        <form action="{{ route('fe.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-sm font-medium">Xóa</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
