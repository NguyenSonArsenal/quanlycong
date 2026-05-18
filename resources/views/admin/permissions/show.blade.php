@extends('layouts.app')
@section('title', 'Cấu hình chi tiết: ' . $role->title)
@section('content')

{{-- Back breadcrumb --}}
<div class="mb-4">
    <a href="{{ route('fe.admin.permissions') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 transition-colors font-medium">
        ⬅ Quay lại danh sách nhóm chức vụ
    </a>
</div>

{{-- Header --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-800 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-sm uppercase">
                {{ substr($role->name, 0, 4) }}
            </div>
            <div>
                <h1 class="text-sm font-black text-slate-800 flex items-center gap-2">
                    {{ $role->title }}
                    <span class="px-2 py-0.5 border border-slate-200 bg-slate-50 rounded-full text-[9px] font-black text-slate-500 uppercase tracking-wider">
                        {{ $role->name }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $role->description }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
    {{-- Left/Main Column: Permission Assignment (8 cols) --}}
    <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h2 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                🛡️ Thiết lập Quyền hạn
            </h2>
            <span class="text-xs text-slate-400 font-bold">
                {{ count($role->permissions) }} / {{ count($permissions->flatten()) }} quyền đã cấp
            </span>
        </div>

        @if($role->name === 'admin')
        <div class="p-8 text-center bg-rose-50/5">
            <div class="text-4xl mb-3">🛡️</div>
            <h3 class="text-sm font-bold text-rose-500">Quyền Hạn Tuyệt Đối</h3>
            <p class="text-[11px] text-slate-400 mt-1 max-w-md mx-auto">
                Nhóm Admin hệ thống được tự động cấp tất cả mọi quyền lực quản trị. Bạn không cần cấu hình thêm bất kỳ quyền hạn nào cho nhóm này.
            </p>
        </div>
        @else
        <form action="{{ route('fe.admin.permissions.update', $role->id) }}" method="POST" class="p-4">
            @csrf @method('POST')
            
            <div class="space-y-3.5">
                @foreach($permissions as $group => $perms)
                <div class="bg-slate-50/30 border border-slate-100/75 rounded-xl p-3.5">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-200/50">
                        {{ $groupLabels[$group] ?? $group }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                        @foreach($perms as $perm)
                        @php
                            $isChecked = $role->permissions->contains('id', $perm->id);
                        @endphp
                        <label class="flex items-center gap-2 p-1.5 px-2.5 bg-white border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/10 rounded-lg cursor-pointer group transition-all select-none" title="{{ $perm->description }}">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                   {{ $isChecked ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer transition-all shrink-0">
                            <span class="text-xs font-semibold text-slate-700 group-hover:text-slate-900 leading-none truncate">
                                {{ $perm->title }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex items-center gap-2 mt-5 pt-4 border-t border-slate-100">
                <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-indigo-600 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    💾 Lưu cấu hình quyền
                </button>
                <a href="{{ route('fe.admin.permissions') }}"
                   class="px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-500 text-xs font-bold rounded-xl active:scale-95 transition-all">
                    Hủy bỏ
                </a>
            </div>
        </form>
        @endif
    </div>

    {{-- Right Column: Users List (4 cols) --}}
    <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    👥 Nhân sự thuộc nhóm
                </h2>
                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-full text-[10px] font-black shrink-0">
                    {{ count($users) }} người
                </span>
            </div>

            <div class="p-3 max-h-[400px] overflow-y-auto divide-y divide-slate-100">
                @forelse($users as $user)
                <div class="flex items-center gap-2.5 py-2 first:pt-0 last:pb-0">
                    {{-- Small Avatar --}}
                    <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0 select-none">
                        {{ strtoupper(substr($user->full_name, 0, 1)) }}
                    </div>
                    
                    {{-- User info --}}
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-800 text-xs truncate">
                            {{ $user->full_name }}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                            <span>@</span><span>{{ $user->username }}</span>
                            @if($user->store)
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="font-bold text-slate-500">{{ $user->store->name }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Profile View Action --}}
                    <div>
                        <a href="{{ route('fe.profile') . '?user_id=' . $user->id . '&month=' . now()->format('Y-m') }}"
                           class="inline-flex items-center justify-center w-6.5 h-6.5 bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-100 rounded-lg text-slate-400 hover:text-indigo-600 shadow-sm transition-all"
                           title="Xem chi tiết lương & công">
                            🔍
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-6">
                    <div class="text-2xl mb-2">🤷‍♂️</div>
                    <p class="text-xs font-bold text-slate-500">Chưa có nhân sự nào</p>
                    <p class="text-[10px] text-slate-400 mt-1 max-w-[200px] mx-auto">Không tìm thấy nhân viên nào đang được gán chức vụ/vai trò này trong hệ thống.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
