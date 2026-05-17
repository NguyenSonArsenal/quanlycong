<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['store', 'position'])->withTrashed(false);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%$q%")
                    ->orWhere('username', 'like', "%$q%");
            });
        }
        if ($request->filled('store_id'))    $query->where('store_id', $request->store_id);
        if ($request->filled('position_id')) $query->where('position_id', $request->position_id);
        if ($request->filled('role'))        $query->where('role', $request->role);
        if ($request->filled('status'))      $query->where('status', $request->status);

        $users     = $query->select('users.*')
            ->leftJoin('stores', 'users.store_id', '=', 'stores.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->orderByRaw('CASE WHEN users.store_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('stores.code', 'asc')
            ->orderByRaw('CASE WHEN users.position_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('positions.id', 'asc')
            ->orderBy('users.full_name', 'asc')
            ->paginate(20)
            ->withQueryString();
        $stores    = Store::orderBy('code')->get();
        $positions = Position::orderBy('name')->get();

        return view('staff.index', compact('users', 'stores', 'positions'));
    }

    public function create()
    {
        $stores    = Store::orderBy('code')->get();
        $positions = Position::orderBy('name')->get();
        return view('staff.create', compact('stores', 'positions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'      => 'required|string|min:3|max:50|unique:users,username|alpha_dash',
            'password'      => 'required|string|min:4|confirmed',
            'full_name'     => 'required|string|max:100',
            'role'          => 'required|in:admin,store_manager,staff',
            'store_id'      => 'nullable|exists:stores,id',
            'position_id'   => 'nullable|exists:positions,id',
            'contract_type' => 'required|in:CT,TV',
            'hourly_rate'   => 'required|numeric|min:0',
            'status'        => 'required|in:0,1',
        ], [
            'username.required'      => 'Vui lòng nhập tên đăng nhập.',
            'username.unique'        => 'Tên đăng nhập đã tồn tại.',
            'username.alpha_dash'    => 'Tên đăng nhập chỉ được chứa chữ, số, dấu _ và -.',
            'username.min'           => 'Tên đăng nhập tối thiểu 3 ký tự.',
            'password.required'      => 'Vui lòng nhập mật khẩu.',
            'password.min'           => 'Mật khẩu tối thiểu 4 ký tự.',
            'password.confirmed'     => 'Xác nhận mật khẩu không khớp.',
            'full_name.required'     => 'Vui lòng nhập họ và tên.',
            'hourly_rate.required'   => 'Vui lòng nhập lương theo giờ.',
            'hourly_rate.numeric'    => 'Lương theo giờ phải là số.',
        ]);

        User::create([
            'username'      => $data['username'],
            'password'      => Hash::make($data['password']),
            'full_name'     => $data['full_name'],
            'role'          => $data['role'],
            'store_id'      => $data['store_id'] ?? null,
            'position_id'   => $data['position_id'] ?? null,
            'contract_type' => $data['contract_type'],
            'hourly_rate'   => $data['hourly_rate'],
            'status'        => $data['status'],
        ]);

        return redirect()->route('fe.users.index')->with('success', '✅ Thêm nhân sự thành công!');
    }

    public function edit(User $user)
    {
        $stores    = Store::orderBy('code')->get();
        $positions = Position::orderBy('name')->get();
        return view('staff.edit', compact('user', 'stores', 'positions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:100',
            'role'          => 'required|in:admin,store_manager,staff',
            'store_id'      => 'nullable|exists:stores,id',
            'position_id'   => 'nullable|exists:positions,id',
            'contract_type' => 'required|in:CT,TV',
            'hourly_rate'   => 'required|numeric|min:0',
            'status'        => 'required|in:0,1',
            'password'      => 'nullable|string|min:4|confirmed',
        ], [
            'full_name.required'   => 'Vui lòng nhập họ và tên.',
            'hourly_rate.numeric'  => 'Lương theo giờ phải là số.',
            'password.min'         => 'Mật khẩu tối thiểu 4 ký tự.',
            'password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
        ]);

        $updateData = [
            'full_name'     => $data['full_name'],
            'role'          => $data['role'],
            'store_id'      => $data['store_id'] ?? null,
            'position_id'   => $data['position_id'] ?? null,
            'contract_type' => $data['contract_type'],
            'hourly_rate'   => $data['hourly_rate'],
            'status'        => $data['status'],
        ];
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        return redirect()->route('fe.users.index')->with('success', '✅ Cập nhật nhân sự thành công!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', '❌ Không thể xóa tài khoản đang đăng nhập!');
        }
        $user->delete();
        return back()->with('success', '✅ Đã xóa nhân sự!');
    }
}
