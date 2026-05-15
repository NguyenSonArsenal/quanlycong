@extends('layouts.app')

@section('title', 'Quản lý Cửa hàng')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form thêm mới -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="text-base font-semibold text-slate-800 mb-6">Thêm cửa hàng mới</h3>
            <form action="{{ route('fe.stores.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mã cửa hàng</label>
                    <input type="text" name="code" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="VD: K01" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tên cửa hàng</label>
                    <input type="text" name="name" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="VD: K01 - Bà Triệu" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Khu vực</label>
                    <input type="text" name="area_id" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="VD: HN01">
                </div>
                <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg font-medium hover:bg-slate-800 transition-colors">
                    Lưu cửa hàng
                </button>
            </form>
        </div>
    </div>

    <!-- Danh sách -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Mã</th>
                        <th class="px-6 py-4">Tên cửa hàng</th>
                        <th class="px-6 py-4">Khu vực</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stores as $store)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $store->code }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $store->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $store->area_id }}</td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('fe.stores.destroy', $store->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-sm font-medium">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
