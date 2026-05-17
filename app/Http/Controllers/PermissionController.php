<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        // Chỉ admin mới vào được
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('fe.daily.index')->with('error', '❌ Chỉ Admin mới có quyền quản lý phân quyền.');
        }

        // Lấy tất cả 9 nhóm/chức vụ kèm theo permissions hiện tại
        $roles = Role::with('permissions')->orderBy('id')->get();
        $totalPermissions = Permission::count();

        return view('admin.permissions.index', compact('roles', 'totalPermissions'));
    }

    public function show($roleId)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('fe.daily.index')->with('error', '❌ Chỉ Admin mới có quyền quản lý phân quyền.');
        }

        $role = Role::with('permissions')->findOrFail($roleId);
        $permissions = Permission::orderBy('group')->orderBy('order')->get()->groupBy('group');
        $users = $role->getUsers();

        // Labels nhóm permission
        $groupLabels = [
            'stores'     => '🏪 Cửa hàng',
            'attendance' => '📋 Chấm công',
            'staff'      => '👥 Nhân sự',
            'payroll'    => '💰 Bảng lương',
            'kpi'        => '📊 KPI',
            'admin'      => '⚙️ Quản trị',
        ];

        return view('admin.permissions.show', compact('role', 'permissions', 'users', 'groupLabels'));
    }

    public function update(Request $request, $roleId)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role = Role::findOrFail($roleId);

        // sync permissions: nhận array permission IDs
        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);

        return redirect()->back()->with('success', "✅ Đã cập nhật quyền cho nhóm: {$role->title}!");
    }

    /**
     * Reset về default theo nhóm
     */
    public function resetDefault($roleId)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role = Role::findOrFail($roleId);

        $rolePermissionMap = [
            'admin' => [
                'manage_all_stores', 'manage_own_store', 'lock_day', 'unlock_day', 
                'lock_month', 'unlock_month', 'bypass_locked_day', 'manage_staff', 
                'view_payroll_all', 'view_payroll_store', 'config_kpi', 'manage_permissions',
            ],
            'area_manager' => [
                'manage_all_stores', 'view_payroll_all', 'manage_staff',
            ],
            'QLCH' => [
                'manage_own_store', 'bypass_locked_day', 'view_payroll_store', 'manage_staff',
            ],
            'CHP' => [
                'manage_own_store', 'view_payroll_store',
            ],
            'NVBH_FT' => [],
            'NVBH_PT' => [],
            'NVTN' => [],
            'NVK' => [],
            'NVBV' => [],
        ];

        $defaultPerms = $rolePermissionMap[$role->name] ?? [];
        $permIds      = Permission::whereIn('name', $defaultPerms)->pluck('id');
        $role->permissions()->sync($permIds);

        return redirect()->back()->with('success', "✅ Đã reset quyền về mặc định cho nhóm: {$role->title}!");
    }
}
