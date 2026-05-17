<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name', 'title', 'description',
    ];

    /**
     * Many-to-Many: permissions assigned to this role/group
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    /**
     * Lấy danh sách nhân viên thuộc nhóm chức vụ này
     */
    public function getUsers()
    {
        if ($this->name === 'admin' || $this->name === 'area_manager') {
            return User::where('role', $this->name)->with('store')->get();
        }
        return User::whereHas('position', function ($q) {
            $q->where('code', $this->name);
        })->with(['store', 'position'])->get();
    }

    /**
     * Đếm số lượng nhân viên thuộc nhóm chức vụ này
     */
    public function getUsersCount(): int
    {
        if ($this->name === 'admin' || $this->name === 'area_manager') {
            return User::where('role', $this->name)->count();
        }
        return User::whereHas('position', function ($q) {
            $q->where('code', $this->name);
        })->count();
    }

    /**
     * Seed groups/roles helper
     */
    public static function defaultRoles(): array
    {
        return [
            ['name' => 'admin',             'title' => 'Admin / HR',                'description' => 'Quản trị viên hệ thống toàn chuỗi'],
            ['name' => 'area_manager',      'title' => 'Area Manager',              'description' => 'Quản lý khu vực (nhiều cửa hàng)'],
            ['name' => 'QLCH',              'title' => 'Quản lý cửa hàng (QLCH)',    'description' => 'Quản lý tại một cửa hàng cố định'],
            ['name' => 'CHP',               'title' => 'Phó quản lý (CHP)',          'description' => 'Phó quản lý cửa hàng'],
            ['name' => 'NVBH_FT',           'title' => 'Nhân viên bán hàng FT',     'description' => 'Nhân viên bán hàng Full-time'],
            ['name' => 'NVBH_PT',           'title' => 'Nhân viên bán hàng PT',     'description' => 'Nhân viên bán hàng Part-time'],
            ['name' => 'NVTN',              'title' => 'Nhân viên thu ngân',        'description' => 'Nhân viên thu ngân tại quầy'],
            ['name' => 'NVK',               'title' => 'Nhân viên kho',             'description' => 'Nhân viên quản lý kho hàng'],
            ['name' => 'NVBV',              'title' => 'Bảo vệ',                    'description' => 'Bảo vệ an ninh cửa hàng'],
        ];
    }
}
