<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['store', 'position'])->orderBy('full_name')->get();
        $stores = Store::orderBy('code')->get();
        $positions = Position::orderBy('name')->get();
        
        return view('users.index', compact('users', 'stores', 'positions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|unique:users',
            'full_name' => 'required',
            'password' => 'required|min:6',
            'role' => 'required',
            'store_id' => 'nullable|exists:stores,id',
            'position_id' => 'nullable|exists:positions,id',
            'contract_type' => 'nullable',
            'hourly_rate' => 'nullable|numeric',
        ]);

        $data['password'] = Hash::make($data['password']);
        
        User::create($data);
        return back()->with('success', 'Đã thêm nhân sự mới!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Không thể tự xóa chính mình!']);
        }
        $user->delete();
        return back()->with('success', 'Đã xóa nhân sự!');
    }
}
