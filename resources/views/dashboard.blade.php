@extends('layouts.app')

@section('title', 'Tổng quan hệ thống')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <p class="text-slate-500 text-sm font-medium">Tổng cửa hàng</p>
        <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ \App\Models\Store::count() }}</h3>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <p class="text-slate-500 text-sm font-medium">Tổng nhân sự</p>
        <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ \App\Models\User::count() }}</h3>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <p class="text-slate-500 text-sm font-medium">Doanh thu hôm nay</p>
        <h3 class="text-3xl font-bold text-emerald-600 mt-2">0đ</h3>
    </div>
</div>

<div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
    <h2 class="text-xl font-semibold text-slate-800 mb-4">Chào mừng đến với KRIK!</h2>
    <p class="text-slate-500 leading-relaxed">
        Hệ thống đã sẵn sàng. Anh có thể bắt đầu bằng việc kiểm tra danh sách 
        <a href="{{ route('fe.stores.index') }}" class="text-rose-500 font-medium hover:underline">Cửa hàng</a> 
        và <a href="{{ route('fe.users.index') }}" class="text-rose-500 font-medium hover:underline">Nhân sự</a> ở menu bên trái. 
        Đừng quên <a href="{{ route('fe.kpi-config.index') }}" class="text-rose-500 font-medium hover:underline">Cấu hình KPI</a> nhé!
    </p>
</div>
@endsection
