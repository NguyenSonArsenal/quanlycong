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

        // Cache tĩnh cho request
        static $rolePermissions = null;
        if ($rolePermissions === null) {
            $role = Role::where('name', $roleName)->with('permissions')->first();
            $rolePermissions = $role ? $role->permissions->pluck('name') : collect();
        }

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
}
