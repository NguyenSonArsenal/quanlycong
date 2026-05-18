@extends('layouts.app')

@section('title', 'Quản lý Cửa hàng')

@section('content')
<div class="space-y-6">
    <!-- Hàng tiêu đề và nút Thêm mới -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Danh sách Cửa hàng toàn chuỗi</h1>
            <p class="text-sm text-slate-500 mt-1">Quản lý danh sách chi nhánh, mã định danh và thông tin khu vực địa lý của KRIK</p>
        </div>
        @if(auth()->user()->role === 'admin')
        <button onclick="openCreateModal()" class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium transition-all shadow-sm shadow-slate-900/10 hover:shadow-lg hover:shadow-slate-900/20 active:scale-[0.98]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm cửa hàng mới
        </button>
        @endif
    </div>

    <!-- Thanh bộ lọc & Tìm kiếm nâng cao -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <form method="GET" action="{{ route('fe.stores.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Tìm kiếm từ khóa -->
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 focus:bg-white rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 placeholder-slate-400 text-sm" 
                       placeholder="Tìm kiếm theo mã cửa hàng hoặc tên (Ví dụ: Cầu Giấy)...">
            </div>

            <!-- Lọc theo khu vực -->
            <div class="md:col-span-4">
                <select name="area_id" 
                        class="w-full px-4 py-2.5 bg-slate-50/50 focus:bg-white rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm appearance-none cursor-pointer"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 20 20%22%3E%3Cpath stroke=%22%236b7280%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%221.5%22 d=%22m6 8 4 4 4-4%22/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25rem auto;">
                    <option value="">Tất cả khu vực</option>
                    @foreach($areas as $area)
                        <option value="{{ $area }}" {{ request('area_id') === $area ? 'selected' : '' }}>
                            Khu vực {{ $area }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Nút hành động -->
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-semibold text-sm transition-all active:scale-[0.98]">
                    Lọc kết quả
                </button>
                @if(request()->filled('search') || request()->filled('area_id'))
                    <a href="{{ route('fe.stores.index') }}" class="flex items-center justify-center p-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-all" title="Xóa bộ lọc">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Thống kê số lượng kết quả -->
    <div class="flex items-center justify-between px-2">
        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
            @if(request()->filled('search') || request()->filled('area_id'))
                <span class="inline-block w-1.5 h-1.5 bg-purple-500 rounded-full animate-pulse"></span>
                <span>Tìm thấy <strong class="text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg border border-purple-100 font-mono text-sm">{{ $stores->count() }}</strong> cửa hàng khớp với bộ lọc</span>
            @else
                <span class="inline-block w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                <span>Tổng số <strong class="text-slate-800 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200/50 font-mono text-sm">{{ $stores->count() }}</strong> cửa hàng toàn chuỗi KRIK</span>
            @endif
        </div>
    </div>

    <!-- Bảng danh sách cửa hàng full-width sang trọng -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Mã cửa hàng</th>
                        <th class="px-4 py-3">Tên chi nhánh</th>
                        <th class="px-4 py-3">Mã khu vực</th>
                        @if(auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Thao tác xử lý</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($stores as $store)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 font-bold text-slate-900">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg text-xs font-mono border border-slate-200/50">
                                    {{ $store->code }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-medium text-slate-700">{{ $store->name }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $store->area_id ?: 'Chưa set' }}
                                </span>
                            </td>
                            @if(auth()->user()->role === 'admin')
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-3.5">
                                    <!-- Nút Sửa -->
                                    <button onclick="openEditModal('{{ $store->id }}', '{{ $store->code }}', '{{ $store->name }}', '{{ $store->area_id }}')" 
                                            class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900 font-semibold transition-all">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Sửa
                                    </button>

                                    <!-- Nút Xóa -->
                                    <form action="{{ route('fe.stores.destroy', $store->id) }}" method="POST" onsubmit="return confirm('Anh có chắc chắn muốn xóa cửa hàng này không? Tất cả dữ liệu nhân sự liên quan có thể bị ảnh hưởng!')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 text-rose-500 hover:text-rose-700 font-semibold transition-all">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 4 : 3 }}" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="font-medium text-slate-500">Không tìm thấy cửa hàng nào khớp với bộ lọc</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODAL THÊM CỬA HÀNG MỚI ================= -->
<div id="createStoreModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-100 transform transition-all overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4.5 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-lg">Thêm cửa hàng mới</h3>
            <button onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Form -->
        <form action="{{ route('fe.stores.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mã định danh cửa hàng</label>
                <input type="text" name="code" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm" 
                       placeholder="Ví dụ: K15">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tên chi nhánh cửa hàng</label>
                <input type="text" name="name" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm" 
                       placeholder="Ví dụ: KRIK 23 Chùa Bộc">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mã khu vực địa lý</label>
                <input type="text" name="area_id"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm" 
                       placeholder="Ví dụ: HN02">
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-3 pt-3">
                <button type="button" onclick="closeCreateModal()" 
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-semibold text-sm transition-all">
                    Hủy bỏ
                </button>
                <button type="submit" 
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm">
                    Lưu cửa hàng
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL CẬP NHẬT CỬA HÀNG ================= -->
<div id="editStoreModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-100 transform transition-all overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4.5 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-lg">Cập nhật thông tin cửa hàng</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Form -->
        <form id="editStoreForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mã định danh cửa hàng</label>
                <input type="text" id="edit_code" name="code" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm bg-slate-50 cursor-not-allowed" 
                       readonly title="Không thể thay đổi mã cửa hàng mẫu để đảm bảo liên kết dữ liệu">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tên chi nhánh cửa hàng</label>
                <input type="text" id="edit_name" name="name" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm" 
                       placeholder="Ví dụ: KRIK 23 Chùa Bộc">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mã khu vực địa lý</label>
                <input type="text" id="edit_area_id" name="area_id"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/5 outline-none transition-all text-slate-700 text-sm" 
                       placeholder="Ví dụ: HN02">
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-3 pt-3">
                <button type="button" onclick="closeEditModal()" 
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-semibold text-sm transition-all">
                    Hủy bỏ
                </button>
                <button type="submit" 
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Create Modal Actions
    function openCreateModal() {
        const modal = document.getElementById('createStoreModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCreateModal() {
        const modal = document.getElementById('createStoreModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Edit Modal Actions
    function openEditModal(id, code, name, area_id) {
        const modal = document.getElementById('editStoreModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Populate fields
        document.getElementById('edit_code').value = code;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_area_id').value = area_id;

        // Set action URL dynamically
        const form = document.getElementById('editStoreForm');
        form.action = "{{ route('fe.stores.index') }}/" + id;
    }
    function closeEditModal() {
        const modal = document.getElementById('editStoreModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modals on clicking backdrop
    window.onclick = function(event) {
        const createModal = document.getElementById('createStoreModal');
        const editModal = document.getElementById('editStoreModal');
        if (event.target === createModal) {
            closeCreateModal();
        }
        if (event.target === editModal) {
            closeEditModal();
        }
    }
</script>
@endsection
