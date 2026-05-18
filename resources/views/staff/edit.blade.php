@extends('layouts.app')
@section('title', 'Sửa thông tin — ' . $user->full_name)
@section('content')

<div class="max-w-2xl mx-auto">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-5">
        <a href="{{ route('fe.users.index') }}" class="hover:text-slate-700">Nhân sự</a>
        <span>/</span>
        <span class="text-slate-700 font-medium">{{ $user->full_name }}</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center font-black text-white text-lg">
                {{ mb_substr($user->full_name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-white font-black text-lg">✏️ Sửa: {{ $user->full_name }}</h1>
                <p class="text-slate-400 text-[10px] font-mono">{{ '@' . $user->username }}</p>
            </div>
        </div>

        <form action="{{ route('fe.users.update', $user->id) }}" method="POST" class="p-6 space-y-5">
            @csrf @method('PUT')

            {{-- Thông tin cá nhân --}}
            <div class="border border-slate-100 rounded-xl p-4 space-y-4">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Thông tin cá nhân</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Họ và tên <span class="text-rose-500">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}"
                            class="w-full px-3 py-2 rounded-lg border {{ $errors->has('full_name') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm focus:border-blue-300">
                        @error('full_name') <p class="text-rose-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Mật khẩu mới <span class="text-slate-400">(để trống nếu không đổi)</span></label>
                        <input type="password" name="password"
                            class="w-full px-3 py-2 rounded-lg border {{ $errors->has('password') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm focus:border-blue-300"
                            placeholder="Tối thiểu 4 ký tự">
                        @error('password') <p class="text-rose-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Xác nhận mật khẩu mới</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm focus:border-blue-300"
                            placeholder="Nhập lại nếu có đổi MK">
                    </div>
                </div>
            </div>

            {{-- Thông tin công việc --}}
            <div class="border border-slate-100 rounded-xl p-4 space-y-4">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Thông tin công việc</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Cửa hàng</label>
                        <select name="store_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="">-- Không gán --</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ old('store_id', $user->store_id) == $s->id ? 'selected' : '' }}>{{ $s->code }} – {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Chức danh</label>
                        <select name="position_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="">-- Không gán --</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}" {{ old('position_id', $user->position_id) == $p->id ? 'selected' : '' }}>{{ $p->code }} – {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Vai trò hệ thống <span class="text-rose-500">*</span></label>
                        <select name="role" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                            <option value="store_manager" {{ old('role', $user->role) === 'store_manager' ? 'selected' : '' }}>Quản lý cửa hàng</option>
                            <option value="area_manager" {{ old('role', $user->role) === 'area_manager' ? 'selected' : '' }}>Area Manager</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin hệ thống</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Loại hợp đồng <span class="text-rose-500">*</span></label>
                        <select name="contract_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="CT" {{ old('contract_type', $user->contract_type) === 'CT' ? 'selected' : '' }}>CT — Chính thức</option>
                            <option value="TV" {{ old('contract_type', $user->contract_type) === 'TV' ? 'selected' : '' }}>TV — Thời vụ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Lương theo giờ</label>
                        {{-- Lương tự động lấy từ cấu hình chức danh --}}
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 flex items-center justify-between">
                            <span id="hourly-rate-display">{{ number_format($user->hourly_rate, 0, ',', '.') }}đ/giờ</span>
                            <span class="text-[10px] text-slate-400">Ấy từ chức danh</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Để thay đổi lương, cập nhật ở trang <a href="{{ route('fe.settings.index') }}" class="text-blue-500 underline">Cài đặt Catalog</a>.</p>
                        {{-- Hidden input gửi giá trị về server --}}
                        <input type="hidden" name="hourly_rate" id="hourly-rate-input" value="{{ $user->hourly_rate }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Trạng thái</label>
                        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="1" {{ old('status', $user->status) == '1' ? 'selected' : '' }}>✅ Đang làm việc</option>
                            <option value="0" {{ old('status', $user->status) == '0' ? 'selected' : '' }}>❌ Nghỉ việc</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Meta --}}
            <div class="text-[10px] text-slate-400 flex gap-4 border-t border-slate-100 pt-3">
                <span>Tạo lúc: {{ $user->created_at->format('d/m/Y H:i') }}</span>
                <span>Cập nhật: {{ $user->updated_at->format('d/m/Y H:i') }}</span>
            </div>

            {{-- Actions --}}
            <div class="flex justify-between items-center pt-2">
                <a href="{{ route('fe.users.index') }}"
                    class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all">
                    ← Quay lại
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-all shadow-lg shadow-slate-200">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Map position_id -> default_hourly_rate
const positionRates = @json($positions->pluck('default_hourly_rate', 'id'));

const posSelect  = document.querySelector('select[name="position_id"]');
const rateDisplay= document.getElementById('hourly-rate-display');
const rateInput  = document.getElementById('hourly-rate-input');

posSelect.addEventListener('change', function () {
    const posId = this.value;
    const rate  = positionRates[posId] ?? null;
    if (rate !== null) {
        rateInput.value   = rate;
        rateDisplay.textContent = new Intl.NumberFormat('vi-VN').format(rate) + 'đ/giờ';
    } else {
        rateDisplay.textContent = '-- Chọn chức danh --';
    }
});
</script>
@endpush
