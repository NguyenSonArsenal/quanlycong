@extends('layouts.app')
@section('title', 'Cài đặt catalog')
@section('has_local_alert', true)
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-black text-slate-800">🛠️ Cài đặt catalog</h1>
        <p class="text-xs text-slate-400 mt-0.5">Cấu hình lương theo chức danh · Bảng hoa hồng</p>
    </div>
</div>

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm font-medium">
    <span>✅</span> {{ session('success') }}
</div>
@endif

{{-- ══ SECTION 1: Lương mặc định theo chức danh ══ --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 text-base">💼</div>
        <div>
            <h2 class="font-black text-slate-800">Cấu hình lương theo chức danh</h2>
            <p class="text-[10px] text-slate-400">Lương/giờ mặc định · Loại hợp đồng · Thưởng team · Sale</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-3 py-2 text-left">Chức danh</th>
                    <th class="px-2 py-2 text-center">Mã</th>
                    <th class="px-2 py-2 text-center">Sale</th>
                    <th class="px-3 py-2 text-center">Loại HĐ mặc định</th>
                    <th class="px-3 py-2 text-right">Lương/giờ mặc định</th>
                    <th class="px-3 py-2 text-right">Thưởng Team Base</th>
                    <th class="px-2 py-2 text-center w-24">Lưu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($positions as $pos)
                <tr class="{{ $loop->even ? 'bg-slate-50/30' : 'bg-white' }} hover:bg-blue-50/20 transition-colors">
                    {{-- Form định dạng ẩn ngoài cấu trúc cột --}}
                    <form id="form-pos-{{ $pos->id }}" method="POST" action="{{ route('fe.settings.positions.update', $pos->id) }}">
                        @csrf @method('PUT')
                    </form>
                    
                    <td class="px-3 py-2">
                        <div class="font-bold text-slate-800">{{ $pos->name }}</div>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <span class="text-[9px] font-bold font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{{ $pos->code }}</span>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="hidden" name="is_sales" value="0" form="form-pos-{{ $pos->id }}">
                            <input type="checkbox" name="is_sales" value="1"
                                {{ $pos->is_sales ? 'checked' : '' }}
                                class="w-4 h-4 rounded accent-emerald-600" form="form-pos-{{ $pos->id }}">
                            <span class="text-[10px] {{ $pos->is_sales ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                                {{ $pos->is_sales ? 'Có' : 'Không' }}
                            </span>
                        </label>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <select name="default_contract_type"
                            class="px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-slate-700 bg-white"
                            form="form-pos-{{ $pos->id }}">
                            <option value="CT" {{ ($pos->default_contract_type ?? 'CT') === 'CT' ? 'selected' : '' }}>
                                CT — Chính thức
                            </option>
                            <option value="TV" {{ ($pos->default_contract_type ?? '') === 'TV' ? 'selected' : '' }}>
                                TV — Thời vụ
                            </option>
                        </select>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <input type="text" name="default_hourly_rate"
                                value="{{ number_format($pos->default_hourly_rate ?? 0, 0, ',', '.') }}"
                                class="w-28 px-2.5 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-right text-slate-700 focus:border-blue-400 focus:ring-1 focus:ring-blue-100 number-format-input"
                                form="form-pos-{{ $pos->id }}">
                            <span class="text-[9px] text-slate-400">đ/h</span>
                        </div>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <input type="text" name="team_bonus_base"
                                value="{{ number_format($pos->team_bonus_base ?? 0, 0, ',', '.') }}"
                                class="w-32 px-2.5 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-right text-slate-700 focus:border-blue-400 focus:ring-1 focus:ring-blue-100 number-format-input"
                                form="form-pos-{{ $pos->id }}">
                            <span class="text-[9px] text-slate-400">đ</span>
                        </div>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <button type="submit"
                            form="form-pos-{{ $pos->id }}"
                            class="px-2.5 py-1 bg-blue-600 text-white rounded-lg text-[10px] font-bold hover:bg-blue-700 transition-all">
                            Lưu
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Ghi chú --}}
    <div class="px-5 py-3 bg-amber-50/50 border-t border-amber-100">
        <p class="text-[10px] text-amber-700">
            💡 <strong>Lương/giờ mặc định</strong> chỉ dùng làm giá trị gợi ý khi tạo nhân viên mới — không thay đổi lương của nhân viên đang có.
            Để thay đổi lương từng người, vào <a href="{{ route('fe.users.index') }}" class="underline font-bold">Danh sách nhân sự</a>.
        </p>
    </div>
</div>

{{-- ══ SECTION 2: Bảng hoa hồng ══ --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 text-base">💰</div>
            <div>
                <h2 class="font-black text-slate-800">Bảng hoa hồng (Commission Brackets)</h2>
                <p class="text-[10px] text-slate-400">Rate% tương ứng với khoảng KPI đạt được</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse min-w-[1000px]">
            <thead class="bg-slate-800 text-white text-[9px] uppercase font-bold tracking-wider text-center">
                <tr>
                    <th class="px-3 py-2 text-left">Chức danh</th>
                    <th class="px-2 py-2">Loại HĐ</th>
                    <th class="px-2 py-2">Hiệu lực từ</th>
                    <th class="px-2 py-2">Hiệu lực đến</th>
                    <th class="px-2.5 py-2 w-32">&lt; 90% KPI</th>
                    <th class="px-2.5 py-2 w-32">90% - 100% KPI</th>
                    <th class="px-2.5 py-2 w-32">100% - 110% KPI</th>
                    <th class="px-2.5 py-2 w-32">110% - 120% KPI</th>
                    <th class="px-2.5 py-2 w-32">&ge; 120% KPI</th>
                    <th class="px-2 py-2 w-24 text-center">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($bracketsGrouped as $key => $groupBrackets)
                @php
                    $parts = explode('|', $key);
                    $posCode = $parts[0] ?? '';
                    $contractType = $parts[1] ?? '';
                    $effectiveFrom = $parts[2] ?? '';
                    $effectiveTo = $parts[3] ?? '';

                    $b0_90 = $groupBrackets->first(fn($b) => $b->min_kpi == 0 && $b->max_kpi == 90);
                    $b90_100 = $groupBrackets->first(fn($b) => $b->min_kpi == 90 && $b->max_kpi == 100);
                    $b100_110 = $groupBrackets->first(fn($b) => $b->min_kpi == 100 && $b->max_kpi == 110);
                    $b110_120 = $groupBrackets->first(fn($b) => $b->min_kpi == 110 && $b->max_kpi == 120);
                    
                    // Mốc mở rộng
                    $b120_inf = $groupBrackets->first(fn($b) => $b->min_kpi >= 120 && $b->max_kpi === null);
                    $b100_inf = $groupBrackets->first(fn($b) => $b->min_kpi == 100 && $b->max_kpi === null);
                    $b90_inf  = $groupBrackets->first(fn($b) => $b->min_kpi == 90 && $b->max_kpi === null);
                @endphp
                <tr class="{{ $loop->even ? 'bg-slate-50/30' : 'bg-white' }} hover:bg-purple-50/20 transition-colors">
                    <td class="px-3 py-2">
                        <span class="text-[9px] font-bold font-mono bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">{{ $posCode }}</span>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold {{ $contractType === 'TV' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $contractType }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-center text-xs font-bold text-slate-700">
                        {{ $effectiveFrom ? date('d/m/Y', strtotime($effectiveFrom)) : '—' }}
                    </td>
                    <td class="px-2 py-2 text-center text-xs font-medium text-slate-500">
                        {{ $effectiveTo ? date('d/m/Y', strtotime($effectiveTo)) : 'Vô hạn' }}
                    </td>

                    {{-- < 90% --}}
                    <td class="px-2.5 py-2 text-center">
                        @if($b0_90)
                            <div class="inline-flex items-center gap-1 justify-center">
                                <form method="POST" action="{{ route('fe.settings.brackets.update', $b0_90->id) }}" class="flex items-center gap-0.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="commission_rate" value="{{ $b0_90->commission_rate }}" step="0.1" min="0" max="100"
                                        class="w-20 px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center text-slate-700 focus:border-purple-400">
                                    <span class="text-[9px] text-slate-400">%</span>
                                    <button type="submit" title="Lưu" class="px-2 py-1 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-all text-[9px] font-bold">✓</button>
                                </form>
                                <form method="POST" action="{{ route('fe.settings.brackets.destroy', $b0_90->id) }}" onsubmit="return confirm('Xóa mốc này?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Xóa" class="text-[12px] text-slate-300 hover:text-rose-500 font-bold ml-0.5">×</button>
                                </form>
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- 90% - 100% --}}
                    <td class="px-2.5 py-2 text-center">
                        @php $b = $b90_100 ?: $b90_inf; @endphp
                        @if($b)
                            <div class="inline-flex items-center gap-1 justify-center">
                                <form method="POST" action="{{ route('fe.settings.brackets.update', $b->id) }}" class="flex items-center gap-0.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="commission_rate" value="{{ $b->commission_rate }}" step="0.1" min="0" max="100"
                                        class="w-20 px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center text-slate-700 focus:border-purple-400">
                                    <span class="text-[9px] text-slate-400">%</span>
                                    <button type="submit" title="Lưu" class="px-2 py-1 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-all text-[9px] font-bold">✓</button>
                                </form>
                                <form method="POST" action="{{ route('fe.settings.brackets.destroy', $b->id) }}" onsubmit="return confirm('Xóa mốc này?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Xóa" class="text-[12px] text-slate-300 hover:text-rose-500 font-bold ml-0.5">×</button>
                                </form>
                                @if($b90_inf)<span class="text-[8px] text-purple-500 font-bold block mt-0.5" title="Từ 90% trở lên">(≥90%)</span>@endif
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- 100% - 110% --}}
                    <td class="px-2.5 py-2 text-center">
                        @php $b = $b100_110 ?: $b100_inf; @endphp
                        @if($b)
                            <div class="inline-flex items-center gap-1 justify-center">
                                <form method="POST" action="{{ route('fe.settings.brackets.update', $b->id) }}" class="flex items-center gap-0.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="commission_rate" value="{{ $b->commission_rate }}" step="0.1" min="0" max="100"
                                        class="w-20 px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center text-slate-700 focus:border-purple-400">
                                    <span class="text-[9px] text-slate-400">%</span>
                                    <button type="submit" title="Lưu" class="px-2 py-1 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-all text-[9px] font-bold">✓</button>
                                </form>
                                <form method="POST" action="{{ route('fe.settings.brackets.destroy', $b->id) }}" onsubmit="return confirm('Xóa mốc này?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Xóa" class="text-[12px] text-slate-300 hover:text-rose-500 font-bold ml-0.5">×</button>
                                </form>
                                @if($b100_inf)<span class="text-[8px] text-purple-500 font-bold block mt-0.5" title="Từ 100% trở lên">(≥100%)</span>@endif
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- 110% - 120% --}}
                    <td class="px-2.5 py-2 text-center">
                        @if($b110_120)
                            <div class="inline-flex items-center gap-1 justify-center">
                                <form method="POST" action="{{ route('fe.settings.brackets.update', $b110_120->id) }}" class="flex items-center gap-0.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="commission_rate" value="{{ $b110_120->commission_rate }}" step="0.1" min="0" max="100"
                                        class="w-20 px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center text-slate-700 focus:border-purple-400">
                                    <span class="text-[9px] text-slate-400">%</span>
                                    <button type="submit" title="Lưu" class="px-2 py-1 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-all text-[9px] font-bold">✓</button>
                                </form>
                                <form method="POST" action="{{ route('fe.settings.brackets.destroy', $b110_120->id) }}" onsubmit="return confirm('Xóa mốc này?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Xóa" class="text-[12px] text-slate-300 hover:text-rose-500 font-bold ml-0.5">×</button>
                                </form>
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- >= 120% --}}
                    <td class="px-2.5 py-2 text-center">
                        @if($b120_inf)
                            <div class="inline-flex items-center gap-1 justify-center">
                                <form method="POST" action="{{ route('fe.settings.brackets.update', $b120_inf->id) }}" class="flex items-center gap-0.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="commission_rate" value="{{ $b120_inf->commission_rate }}" step="0.1" min="0" max="100"
                                        class="w-20 px-2 py-1 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center text-slate-700 focus:border-purple-400">
                                    <span class="text-[9px] text-slate-400">%</span>
                                    <button type="submit" title="Lưu" class="px-2 py-1 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-all text-[9px] font-bold">✓</button>
                                </form>
                                <form method="POST" action="{{ route('fe.settings.brackets.destroy', $b120_inf->id) }}" onsubmit="return confirm('Xóa mốc này?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Xóa" class="text-[12px] text-slate-300 hover:text-rose-500 font-bold ml-0.5">×</button>
                                </form>
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- Xóa hàng --}}
                    <td class="px-2 py-2 text-center">
                        <form method="POST" action="{{ route('fe.settings.brackets.destroy_group', [$posCode, $contractType]) }}?effective_from={{ $effectiveFrom }}"
                            onsubmit="return confirm('Bạn có chắc muốn xóa TOÀN BỘ hàng hoa hồng này của {{ $posCode }} ({{ $contractType }}) áp dụng từ {{ date('d/m/Y', strtotime($effectiveFrom)) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2 py-1 bg-rose-50 text-rose-600 rounded-lg text-[9px] font-bold hover:bg-rose-600 hover:text-white transition-all flex items-center gap-1 mx-auto" title="Xóa toàn bộ hàng">
                                🗑️ Xóa
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Thêm bracket mới --}}
    <div class="px-5 py-4 bg-slate-50/50 border-t border-slate-100">
        <h3 class="text-xs font-black text-slate-600 mb-3">➕ Thêm bracket mới</h3>
        <form method="POST" action="{{ route('fe.settings.brackets.store') }}"
            class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Chức danh</label>
                <select name="position_code" class="px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs text-slate-700 bg-white" required>
                    @foreach($positions as $pos)
                    <option value="{{ $pos->code }}">{{ $pos->code }} — {{ $pos->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Loại HĐ</label>
                <select name="contract_type" class="px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-bold text-slate-700 bg-white" required>
                    <option value="CT">CT — Chính thức</option>
                    <option value="TV">TV — Thời vụ</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">KPI từ (%)</label>
                <input type="number" name="min_kpi" min="0" max="200" step="5" placeholder="90"
                    class="w-24 px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center" required>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">KPI đến (% — bỏ trống = ∞)</label>
                <input type="number" name="max_kpi" min="0" max="300" step="5" placeholder="100"
                    class="w-24 px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center">
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Rate hoa hồng (%)</label>
                <input type="number" name="commission_rate" min="0" max="100" step="0.1" placeholder="2.5"
                    class="w-24 px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-bold text-center" required>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Hiệu lực từ</label>
                <input type="date" name="effective_from" value="{{ date('Y-m-d') }}"
                    class="px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-medium font-sans" required>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Hiệu lực đến (bỏ trống = vô hạn)</label>
                <input type="date" name="effective_to"
                    class="px-3 py-2 rounded-lg border border-slate-200 outline-none text-xs font-medium font-sans">
            </div>
            <button type="submit"
                class="px-5 py-2 bg-purple-700 text-white rounded-lg text-xs font-bold hover:bg-purple-800 transition-all">
                ➕ Thêm bracket
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatInputs = document.querySelectorAll('.number-format-input');

    // Hàm format số thành dạng 50.000, 5.000.000
    function formatValue(value) {
        // Loại bỏ mọi ký tự không phải số
        let clean = value.replace(/\D/g, '');
        if (!clean) return '0';
        // Chuyển sang dạng integer để loại bỏ số 0 thừa ở đầu
        return parseInt(clean, 10).toLocaleString('vi-VN');
    }

    // Lắng nghe sự kiện gõ phím
    formatInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            // Lưu lại vị trí con trỏ trước khi format
            let cursorPosition = e.target.selectionStart;
            let originalLength = e.target.value.length;

            let formatted = formatValue(e.target.value);
            e.target.value = formatted;

            // Điều chỉnh vị trí con trỏ
            let newLength = formatted.length;
            cursorPosition = cursorPosition + (newLength - originalLength);
            e.target.setSelectionRange(cursorPosition, cursorPosition);
        });
    });

    // Strip toàn bộ dấu chấm phân tách trước khi submit form
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            document.querySelectorAll('.number-format-input').forEach(input => {
                if (input.form === form || form.contains(input)) {
                    input.value = input.value.replace(/\./g, '');
                }
            });
        });
    });
});
</script>
@endpush

