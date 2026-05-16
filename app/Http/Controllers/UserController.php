<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['store', 'position']);

        // Áp dụng bộ lọc
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('full_name', 'like', "%$q%")
                    ->orWhere('username', 'like', "%$q%");
            });
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        $stores = Store::orderBy('code')->get();
        $positions = Position::orderBy('name')->get();

        return view('users.index', compact('users', 'stores', 'positions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required|min:4',
            'full_name' => 'required',
            'role' => 'required'
        ]);

        User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'role' => $request->role,
            'store_id' => $request->store_id,
            'position_id' => $request->position_id,
            'salary_per_hour' => $request->salary_per_hour ?? 0,
            'contract_type' => $request->contract_type ?? 'CT',
        ]);

        return back()->with('success', 'Thêm nhân sự thành công!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'Đã xóa nhân sự!');
    }
}
