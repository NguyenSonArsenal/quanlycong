@extends('layouts.app')
@section('title', 'Quản lý Nhóm chức vụ & Phân quyền')
@section('has_local_alert', true)
@section('content')

{{-- Flash alerts --}}
@if(session('success'))
<div class="mb-5 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium flex items-center gap-2">
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-5 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-medium">
    {{ session('error') }}
</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-black text-slate-800">🔐 Danh sách Nhóm / Chức vụ hệ thống</h1>
        <p class="text-xs text-slate-400 mt-1">Quản lý phân quyền tập trung và theo dõi danh sách nhân viên cho từng vai trò cố định của KRIK</p>
    </div>
    <div class="flex items-center gap-2 text-xs text-slate-500 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 shadow-sm max-w-sm">
        <span class="text-amber-500 font-bold text-sm">⚠️</span>
        <div>Nhóm <span class="font-bold text-slate-700">Admin</span> luôn có toàn bộ quyền tối cao trong hệ thống và không cần cấu hình.</div>
    </div>
</div>

{{-- Group List Table Card --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 text-xs font-bold uppercase tracking-wider">
                    <th class="px-6 py-4">Nhóm Chức Vụ</th>
                    <th class="px-6 py-4">Quyền Hạn Được Cấp</th>
                    <th class="px-6 py-4">Nhân Sự Trực Thuộc</th>
                    <th class="px-6 py-4 text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($roles as $role)
                @php
                    $rolePermIds = $role->permissions->pluck('id')->toArray();
                    $isAdmin     = $role->name === 'admin';
                    $usersCount  = $role->getUsersCount();
                    
                    $roleColors  = [
                        'admin'        => 'bg-rose-100 text-rose-700 border-rose-200',
                        'area_manager' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                        'QLCH'         => 'bg-blue-100 text-blue-700 border-blue-200',
                        'CHP'          => 'bg-cyan-100 text-cyan-700 border-cyan-200',
                        'NVBH_FT'      => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        'NVBH_PT'      => 'bg-teal-100 text-teal-700 border-teal-200',
                        'NVTN'         => 'bg-amber-100 text-amber-700 border-amber-200',
                        'NVK'          => 'bg-violet-100 text-violet-700 border-violet-200',
                        'NVBV'         => 'bg-slate-100 text-slate-600 border-slate-200',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    {{-- Group Column --}}
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-white font-black text-xs shrink-0 shadow-sm uppercase">
                                {{ substr($role->name, 0, 4) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                    {{ $role->title }}
                                    <span class="px-2 py-0.5 border rounded-full text-[9px] font-black {{ $roleColors[$role->name] ?? 'bg-slate-50 text-slate-500 border-slate-200' }}">
                                        {{ $role->name }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-1 max-w-md line-clamp-1" title="{{ $role->description }}">
                                    {{ $role->description }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Granted Permissions Status --}}
                    <td class="px-6 py-5">
                        @if($isAdmin)
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                            <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-lg">Tất cả mọi quyền</span>
                        </div>
                        @else
                        @php
                            $pct = $totalPermissions > 0 ? (count($rolePermIds) / $totalPermissions) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                <span class="font-bold text-slate-700">{{ count($rolePermIds) }} / {{ $totalPermissions }} quyền</span>
                                <span class="font-medium text-slate-400">{{ round($pct) }}%</span>
                            </div>
                            <div class="w-36 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @endif
                    </td>

                    {{-- Users in group --}}
                    <td class="px-6 py-5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700">
                            👥 {{ $usersCount }} nhân sự
                        </span>
                    </td>

                    {{-- Action buttons --}}
                    <td class="px-6 py-5 text-center">
                        <a href="{{ route('fe.admin.permissions.show', $role->id) }}"
                           class="inline-flex items-center gap-1.5 px-4  py-2 bg-slate-900 text-white hover:bg-indigo-600 active:scale-95 text-xs font-bold rounded-xl shadow-sm transition-all">
                            ⚙️ Chi tiết & Cấp quyền
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
