<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can(['manage_all_stores', 'manage_own_store'])) {
                abort(403, '❌ Bạn không có quyền quản lý cửa hàng.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Store::query();
        $authUser = auth()->user();

        // Phân quyền hiển thị danh sách cửa hàng
        if ($authUser->role === 'admin') {
            // Admin thấy toàn bộ
        } elseif ($authUser->role === 'area_manager') {
            // Area Manager thấy các store cùng khu vực
            $areaId = $authUser->store ? $authUser->store->area_id : null;
            $query->where('area_id', $areaId);
        } else {
            // QLCH / CHP chỉ thấy duy nhất cửa hàng của mình
            $query->where('id', $authUser->store_id);
        }

        // Lọc theo từ khóa (Mã hoặc Tên cửa hàng)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Lọc theo khu vực (area_id)
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        $stores = $query->orderBy('code')->get();

        // Lấy danh sách các khu vực duy nhất trong db để đổ ra bộ lọc theo phạm vi quyền
        $areasQuery = Store::whereNotNull('area_id')
            ->where('area_id', '<>', '');

        if ($authUser->role === 'admin') {
            // Admin thấy tất cả khu vực
        } elseif ($authUser->role === 'area_manager') {
            $areaId = $authUser->store ? $authUser->store->area_id : null;
            $areasQuery->where('area_id', $areaId);
        } else {
            $areasQuery->where('id', $authUser->store_id);
        }

        $areas = $areasQuery->distinct()
            ->orderBy('area_id')
            ->pluck('area_id');

        return view('stores.index', compact('stores', 'areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'code' => 'required|unique:stores',
            'area_id' => 'nullable',
        ]);

        Store::create($data);
        return back()->with('success', 'Đã thêm cửa hàng mới!');
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'required',
            'code' => 'required|unique:stores,code,' . $store->id,
            'area_id' => 'nullable',
        ]);

        $store->update($data);
        return back()->with('success', 'Đã cập nhật thông tin cửa hàng!');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return back()->with('success', 'Đã xóa cửa hàng!');
    }
}
