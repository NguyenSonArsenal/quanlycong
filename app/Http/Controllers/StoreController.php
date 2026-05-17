<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::query();

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

        // Lấy danh sách các khu vực duy nhất trong db để đổ ra bộ lọc
        $areas = Store::whereNotNull('area_id')
            ->where('area_id', '<>', '')
            ->distinct()
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
