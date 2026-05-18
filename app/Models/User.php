<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'role',
        'store_id',
        'position_id',
        'contract_type',
        'hourly_rate',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function isSales()
    {
        return $this->position ? $this->position->is_sales : false;
    }

    /**
     * Xác định tên nhóm người dùng dựa trên role/position
     */
    public function getGroupRoleName(): string
    {
        if ($this->role === 'admin') {
            return 'admin';
        }
        if ($this->role === 'area_manager') {
            return 'area_manager';
        }
        if ($this->position) {
            return $this->position->code; // QLCH, CHP, NVBH_FT, NVBH_PT, NVTN, NVK, NVBV
        }
        return 'staff'; // fallback
    }

    public function can($abilities, $arguments = [])
    {
        // Admin luôn có tất cả quyền
        if ($this->role === 'admin') return true;

        $roleName = $this->getGroupRoleName();

        // Cache tĩnh cho request theo roleName để tránh ô nhiễm chéo giữa các user/role khác nhau
        static $cachedPermissions = [];
        if (!isset($cachedPermissions[$roleName])) {
            $role = Role::where('name', $roleName)->with('permissions')->first();
            $cachedPermissions[$roleName] = $role ? $role->permissions->pluck('name') : collect();
        }

        $rolePermissions = $cachedPermissions[$roleName];
        $check = is_array($abilities) ? $abilities : [$abilities];

        foreach ($check as $p) {
            if ($rolePermissions->contains($p)) return true;
        }
        return false;
    }

    /**
     * Kiểm tra role
     * @param string|array $role
     */
    public function hasRole($role): bool
    {
        $roles = is_array($role) ? $role : [$role];
        return in_array($this->role, $roles);
    }

    /**
     * Kiểm tra quyền quản lý một nhân sự cụ thể
     */
    public function canManageUser(User $targetUser): bool
    {
        // 1. Admin luôn có toàn quyền
        if ($this->role === 'admin') {
            return true;
        }

        // 2. Tự quản lý chính mình
        if ($this->id === $targetUser->id) {
            return true;
        }

        // 3. Area Manager
        if ($this->role === 'area_manager') {
            // Sửa NV trong các store thuộc khu vực của mình
            if ($this->store && $targetUser->store && $this->store->area_id === $targetUser->store->area_id) {
                // Area Manager có thể sửa bất kỳ ai trong khu vực ngoại trừ admin và area_manager khác
                return $targetUser->role !== 'admin' && $targetUser->role !== 'area_manager';
            }
            return false;
        }

        // 4. Store Manager (QLCH)
        if ($this->getGroupRoleName() === 'QLCH') {
            // Phải cùng store
            if ($this->store_id && $this->store_id == $targetUser->store_id) {
                // Không sửa được QLCH đồng cấp khác
                $targetGroup = $targetUser->getGroupRoleName();
                return $targetGroup !== 'admin' && $targetGroup !== 'area_manager' && $targetGroup !== 'QLCH';
            }
            return false;
        }

        // 5. Phó quản lý (CHP)
        if ($this->getGroupRoleName() === 'CHP') {
            // Phải cùng store
            if ($this->store_id && $this->store_id == $targetUser->store_id) {
                // Không sửa được QLCH (cấp trên) và CHP đồng cấp khác
                $targetGroup = $targetUser->getGroupRoleName();
                return !in_array($targetGroup, ['admin', 'area_manager', 'QLCH', 'CHP']);
            }
            return false;
        }

        // 6. Nhân viên thường (Sales Staff / Cashier / Guard etc.)
        // Chỉ sửa được chính mình (đã check ở bước 2)
        return false;
    }
}
