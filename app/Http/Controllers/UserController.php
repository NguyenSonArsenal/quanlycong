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
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('manage_staff')) {
                abort(403, '❌ Bạn không có quyền quản lý nhân sự.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query = User::with(['store', 'position'])->withTrashed(false);

        // Giới hạn hiển thị nhân sự theo scope của vai trò
        if ($authUser->role === 'admin') {
            // Admin thấy toàn bộ
        } elseif ($authUser->role === 'area_manager') {
            // Area Manager thấy các store cùng khu vực
            if ($authUser->store) {
                $storeIds = Store::where('area_id', $authUser->store->area_id)->pluck('id');
                $query->whereIn('store_id', $storeIds);
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($authUser->can('manage_own_store')) {
            // QLCH / CHP thấy NV trong cửa hàng của mình
            $query->where('store_id', $authUser->store_id);
        } else {
            // Nhân viên thường chỉ thấy chính mình
            $query->where('id', $authUser->id);
        }

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
        $authUser = auth()->user();
        if ($authUser->role === 'admin') {
            $stores = Store::orderBy('code')->get();
        } elseif ($authUser->role === 'area_manager') {
            $stores = Store::where('area_id', $authUser->store ? $authUser->store->area_id : null)->orderBy('code')->get();
        } else {
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        }
        $positions = Position::orderBy('name')->get();

        return view('staff.index', compact('users', 'stores', 'positions'));
    }

    public function create()
    {
        $authUser = auth()->user();
        if ($authUser->role === 'admin') {
            $stores = Store::orderBy('code')->get();
        } elseif ($authUser->role === 'area_manager') {
            $stores = Store::where('area_id', $authUser->store ? $authUser->store->area_id : null)->orderBy('code')->get();
        } else {
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        }
        $positions = Position::orderBy('name')->get();
        return view('staff.create', compact('stores', 'positions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'      => 'required|string|min:3|max:50|unique:users,username|alpha_dash',
            'password'      => 'required|string|min:4|confirmed',
            'full_name'     => 'required|string|max:100',
            'role'          => 'required|in:admin,area_manager,store_manager,staff',
            'store_id'      => 'nullable|exists:stores,id',
            'position_id'   => 'nullable|exists:positions,id',
            'contract_type' => 'required|in:CT,TV',
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
        ]);

        $authUser = auth()->user();
        if ($authUser->role !== 'admin') {
            if ($authUser->role === 'area_manager') {
                $targetStore = Store::find($request->store_id);
                if (!$targetStore || $targetStore->area_id !== ($authUser->store ? $authUser->store->area_id : null)) {
                    return back()->withErrors(['store_id' => '❌ Cửa hàng không thuộc khu vực quản lý của bạn.'])->withInput();
                }
                if (in_array($request->role, ['admin', 'area_manager'])) {
                    return back()->withErrors(['role' => '❌ Bạn không có quyền tạo vai trò Admin hoặc Area Manager.'])->withInput();
                }
            } else {
                if ($request->store_id != $authUser->store_id) {
                    return back()->withErrors(['store_id' => '❌ Bạn chỉ được phép thêm nhân viên thuộc cửa hàng của mình.'])->withInput();
                }
                $targetRole = $request->role;
                $pos = Position::find($request->position_id);
                $targetPosCode = $pos ? $pos->code : '';

                if ($authUser->getGroupRoleName() === 'QLCH') {
                    if (in_array($targetRole, ['admin', 'area_manager']) || $targetPosCode === 'QLCH') {
                        return back()->withErrors(['role' => '❌ Bạn không được phép tạo nhân sự có vai trò QLCH hoặc cao hơn.'])->withInput();
                    }
                } elseif ($authUser->getGroupRoleName() === 'CHP') {
                    if (in_array($targetRole, ['admin', 'area_manager']) || in_array($targetPosCode, ['QLCH', 'CHP'])) {
                        return back()->withErrors(['role' => '❌ Bạn không được phép tạo nhân sự có vai trò CHP hoặc cao hơn.'])->withInput();
                    }
                } else {
                    abort(403, '❌ Bạn không có quyền tạo nhân sự.');
                }
            }
        }

        // Tự động lấy hourly_rate từ default_hourly_rate của chức danh được chọn
        $position  = $data['position_id'] ? Position::find($data['position_id']) : null;
        $hourlyRate = $position ? (float)$position->default_hourly_rate : 0;

        User::create([
            'username'      => $data['username'],
            'password'      => Hash::make($data['password']),
            'full_name'     => $data['full_name'],
            'role'          => $data['role'],
            'store_id'      => $data['store_id'] ?? null,
            'position_id'   => $data['position_id'] ?? null,
            'contract_type' => $data['contract_type'],
            'hourly_rate'   => $hourlyRate,
            'status'        => $data['status'],
        ]);

        return redirect()->route('fe.users.index')->with('success', '✅ Thêm nhân sự thành công!');
    }

    public function edit(User $user)
    {
        if (!auth()->user()->canManageUser($user)) {
            abort(403, '❌ Bạn không có quyền chỉnh sửa nhân viên này.');
        }

        $authUser = auth()->user();
        if ($authUser->role === 'admin') {
            $stores = Store::orderBy('code')->get();
        } elseif ($authUser->role === 'area_manager') {
            $stores = Store::where('area_id', $authUser->store ? $authUser->store->area_id : null)->orderBy('code')->get();
        } else {
            $stores = Store::where('id', $authUser->store_id)->orderBy('code')->get();
        }
        $positions = Position::orderBy('name')->get();
        return view('staff.edit', compact('user', 'stores', 'positions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:100',
            'role'          => 'required|in:admin,area_manager,store_manager,staff',
            'store_id'      => 'nullable|exists:stores,id',
            'position_id'   => 'nullable|exists:positions,id',
            'contract_type' => 'required|in:CT,TV',
            'status'        => 'required|in:0,1',
            'password'      => 'nullable|string|min:4|confirmed',
        ], [
            'full_name.required'   => 'Vui lòng nhập họ và tên.',
            'password.min'         => 'Mật khẩu tối thiểu 4 ký tự.',
            'password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
        ]);

        if (!auth()->user()->canManageUser($user)) {
            abort(403, '❌ Bạn không có quyền chỉnh sửa nhân viên này.');
        }

        $authUser = auth()->user();
        if ($authUser->role !== 'admin') {
            if ($authUser->role === 'area_manager') {
                $targetStore = Store::find($request->store_id);
                if (!$targetStore || $targetStore->area_id !== ($authUser->store ? $authUser->store->area_id : null)) {
                    return back()->withErrors(['store_id' => '❌ Cửa hàng không thuộc khu vực quản lý của bạn.'])->withInput();
                }
                if (in_array($request->role, ['admin', 'area_manager'])) {
                    return back()->withErrors(['role' => '❌ Bạn không có quyền gán vai trò Admin hoặc Area Manager.'])->withInput();
                }
            } else {
                if ($request->store_id != $authUser->store_id) {
                    return back()->withErrors(['store_id' => '❌ Bạn chỉ được phép gán nhân viên thuộc cửa hàng của mình.'])->withInput();
                }
                $targetRole = $request->role;
                $pos = Position::find($request->position_id);
                $targetPosCode = $pos ? $pos->code : '';

                if ($authUser->getGroupRoleName() === 'QLCH') {
                    if (in_array($targetRole, ['admin', 'area_manager']) || $targetPosCode === 'QLCH') {
                        return back()->withErrors(['role' => '❌ Bạn không được phép nâng cấp nhân sự lên vai trò QLCH hoặc cao hơn.'])->withInput();
                    }
                } elseif ($authUser->getGroupRoleName() === 'CHP') {
                    if (in_array($targetRole, ['admin', 'area_manager']) || in_array($targetPosCode, ['QLCH', 'CHP'])) {
                        return back()->withErrors(['role' => '❌ Bạn không được phép nâng cấp nhân sự lên vai trò CHP hoặc cao hơn.'])->withInput();
                    }
                } else {
                    abort(403, '❌ Bạn không có quyền cập nhật nhân sự.');
                }
            }
        }

        // Tự động lấy hourly_rate mới từ chức danh nếu position thay đổi
        $position   = !empty($data['position_id']) ? Position::find($data['position_id']) : null;
        $hourlyRate = $position ? (float)$position->default_hourly_rate : (float)$user->hourly_rate;

        $updateData = [
            'full_name'     => $data['full_name'],
            'role'          => $data['role'],
            'store_id'      => $data['store_id'] ?? null,
            'position_id'   => $data['position_id'] ?? null,
            'contract_type' => $data['contract_type'],
            'hourly_rate'   => $hourlyRate,
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
        if (!auth()->user()->canManageUser($user)) {
            abort(403, '❌ Bạn không có quyền xóa nhân viên này.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', '❌ Không thể xóa tài khoản đang đăng nhập!');
        }
        $user->delete();
        return back()->with('success', '✅ Đã xóa nhân sự!');
    }
}
