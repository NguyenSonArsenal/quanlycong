<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::orderBy('code')->get();
        return view('stores.index', compact('stores'));
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

    public function destroy(Store $store)
    {
        $store->delete();
        return back()->with('success', 'Đã xóa cửa hàng!');
    }
}
