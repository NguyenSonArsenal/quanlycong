<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\CommissionBracket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, '❌ Chỉ Admin mới có quyền cấu hình hệ thống.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $positions  = Position::orderBy('id')->get();
        $brackets   = DB::table('commission_brackets')
            ->orderBy('position_code')
            ->orderBy('contract_type')
            ->orderBy('min_kpi')
            ->get();

        // Group brackets by position_code + contract_type + effective_from + effective_to
        $bracketsGrouped = $brackets->groupBy(fn($b) => $b->position_code . '|' . $b->contract_type . '|' . $b->effective_from . '|' . $b->effective_to);

        return view('settings.index', compact('positions', 'brackets', 'bracketsGrouped'));
    }

    // ── Cập nhật lương mặc định 1 chức danh ──────────────────────
    public function updatePosition(Request $request, Position $position)
    {
        // Loại bỏ dấu chấm phân tách phần nghìn trước khi validate & lưu
        if ($request->has('default_hourly_rate')) {
            $request->merge([
                'default_hourly_rate' => str_replace('.', '', $request->input('default_hourly_rate'))
            ]);
        }
        if ($request->has('team_bonus_base')) {
            $request->merge([
                'team_bonus_base' => str_replace('.', '', $request->input('team_bonus_base'))
            ]);
        }

        $request->validate([
            'default_hourly_rate'    => 'required|numeric|min:0',
            'default_contract_type'  => 'required|in:CT,TV',
            'team_bonus_base'        => 'required|numeric|min:0',
            'is_sales'               => 'boolean',
        ]);

        $position->update([
            'default_hourly_rate'   => $request->default_hourly_rate,
            'default_contract_type' => $request->default_contract_type,
            'team_bonus_base'       => $request->team_bonus_base,
            'is_sales'              => $request->boolean('is_sales'),
        ]);

        // Tự động đồng bộ lương giờ mới cho toàn bộ nhân sự đang hoạt động thuộc vị trí/chức danh này
        \App\Models\User::where('position_id', $position->id)
            ->where('status', 1)
            ->update([
                'hourly_rate' => $request->default_hourly_rate,
            ]);

        return back()->with('success', "Đã cập nhật cấu hình lương cho [{$position->name}] và tự động đồng bộ lương mới cho toàn bộ nhân sự thuộc vị trí này!");
    }

    // ── Cập nhật 1 bracket hoa hồng ──────────────────────────────
    public function updateBracket(Request $request, int $id)
    {
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::table('commission_brackets')->where('id', $id)->update([
            'commission_rate' => $request->commission_rate,
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Đã cập nhật bảng hoa hồng');
    }

    // ── Thêm bracket mới ─────────────────────────────────────────
    public function storeBracket(Request $request)
    {
        $request->validate([
            'position_code'   => 'required|string',
            'contract_type'   => 'required|in:CT,TV',
            'min_kpi'         => 'required|numeric|min:0',
            'max_kpi'         => 'nullable|numeric|gt:min_kpi',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'effective_from'  => 'required|date',
            'effective_to'    => 'nullable|date|after_or_equal:effective_from',
        ]);

        DB::table('commission_brackets')->insert([
            'position_code'   => $request->position_code,
            'contract_type'   => $request->contract_type,
            'min_kpi'         => $request->min_kpi,
            'max_kpi'         => $request->max_kpi ?: null,
            'commission_rate' => $request->commission_rate,
            'effective_from'  => $request->effective_from,
            'effective_to'    => $request->effective_to ?: null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Đã thêm mốc hoa hồng mới thành công');
    }

    // ── Xóa bracket ──────────────────────────────────────────────
    public function destroyBracket(int $id)
    {
        DB::table('commission_brackets')->where('id', $id)->delete();
        return back()->with('success', 'Đã xóa bracket hoa hồng');
    }

    // ── Xóa toàn bộ dòng brackets hoa hồng ───────────────────────
    public function destroyGroup(Request $request, string $positionCode, string $contractType)
    {
        $query = DB::table('commission_brackets')
            ->where('position_code', $positionCode)
            ->where('contract_type', $contractType);

        if ($request->has('effective_from')) {
            $query->where('effective_from', $request->query('effective_from'));
        }

        $query->delete();

        return back()->with('success', "Đã xóa hàng hoa hồng của [{$positionCode}] loại HĐ [{$contractType}] thành công");
    }
}
