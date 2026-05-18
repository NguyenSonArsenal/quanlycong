@extends('layouts.app')
@section('title', 'Thêm Nhân sự mới')
@section('content')

<div class="max-w-2xl mx-auto">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-5">
        <a href="{{ route('fe.users.index') }}" class="hover:text-slate-700">Nhân sự</a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Thêm mới</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="bg-slate-800 px-6 py-4">
            <h1 class="text-white font-black text-lg">➕ Thêm nhân sự mới</h1>
            <p class="text-slate-400 text-xs mt-0.5">Điền đầy đủ thông tin bên dưới</p>
        </div>

        <form action="{{ route('fe.users.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            {{-- Thông tin đăng nhập --}}
            <div class="border border-slate-100 rounded-xl p-4 space-y-4">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Thông tin đăng nhập</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Tên đăng nhập <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}"
                            class="w-full px-3 py-2 rounded-lg border {{ $errors->has('username') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm focus:border-blue-300"
                            placeholder="vd: k01_nvbh1">
                        @error('username') <p class="text-rose-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Họ và tên <span class="text-rose-500">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                            class="w-full px-3 py-2 rounded-lg border {{ $errors->has('full_name') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm focus:border-blue-300"
                            placeholder="Nguyễn Văn A">
                        @error('full_name') <p class="text-rose-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Mật khẩu <span class="text-rose-500">*</span></label>
                        <input type="password" name="password"
                            class="w-full px-3 py-2 rounded-lg border {{ $errors->has('password') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm focus:border-blue-300"
                            placeholder="Tối thiểu 4 ký tự">
                        @error('password') <p class="text-rose-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Xác nhận mật khẩu <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm focus:border-blue-300"
                            placeholder="Nhập lại mật khẩu">
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
                                <option value="{{ $s->id }}" {{ old('store_id') == $s->id ? 'selected' : '' }}>{{ $s->code }} – {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Chức danh</label>
                        <select name="position_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="">-- Không gán --</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}" {{ old('position_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} – {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Vai trò hệ thống <span class="text-rose-500">*</span></label>
                        <select name="role" class="w-full px-3 py-2 rounded-lg border {{ $errors->has('role') ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }} outline-none text-sm">
                            <option value="staff" {{ old('role','staff') === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                            <option value="store_manager" {{ old('role') === 'store_manager' ? 'selected' : '' }}>Quản lý cửa hàng</option>
                            <option value="area_manager" {{ old('role') === 'area_manager' ? 'selected' : '' }}>Area Manager</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin hệ thống</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Loại hợp đồng <span class="text-rose-500">*</span></label>
                        <select name="contract_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="CT" {{ old('contract_type','CT') === 'CT' ? 'selected' : '' }}>CT — Chính thức</option>
                            <option value="TV" {{ old('contract_type') === 'TV' ? 'selected' : '' }}>TV — Thời vụ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Lương theo giờ</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 flex items-center justify-between">
                            <span id="hourly-rate-display">— Chọn chức danh —</span>
                            <span class="text-[10px] text-slate-400">Ấy từ chức danh</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Được tự động lấy theo cấu hình lương của chức danh đã chọn.</p>
                        <input type="hidden" name="hourly_rate" id="hourly-rate-input" value="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Trạng thái</label>
                        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 outline-none text-sm">
                            <option value="1" {{ old('status','1') === '1' ? 'selected' : '' }}>✅ Đang làm việc</option>
                            <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>❌ Nghỉ việc</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('fe.users.index') }}"
                    class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all">
                    Huỷ
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-all shadow-lg shadow-slate-200">
                    Lưu nhân sự
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const positionRates = @json($positions->pluck('default_hourly_rate', 'id'));

const posSelect   = document.querySelector('select[name="position_id"]');
const rateDisplay = document.getElementById('hourly-rate-display');
const rateInput   = document.getElementById('hourly-rate-input');

function syncRate() {
    const posId = posSelect.value;
    const rate  = positionRates[posId] ?? null;
    if (rate !== null && rate > 0) {
        rateInput.value = rate;
        rateDisplay.textContent = new Intl.NumberFormat('vi-VN').format(rate) + 'đ/giờ';
    } else {
        rateInput.value = 0;
        rateDisplay.textContent = '— Chọn chức danh —';
    }
}

posSelect.addEventListener('change', syncRate);
// Trigger on load if already selected
if (posSelect.value) syncRate();
</script>
@endpush
