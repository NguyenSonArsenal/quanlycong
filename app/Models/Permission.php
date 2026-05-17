<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name', 'title', 'description', 'group', 'order',
    ];

    /**
     * Các users được cấp permission này
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id');
    }

    /**
     * Tất cả permissions nhóm theo group key
     */
    public static function allGrouped(): array
    {
        return static::orderBy('group')->orderBy('order')->get()
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Map permission mặc định theo role
     */
    public static function defaultForRole(string $role): array
    {
        $map = [
            'admin' => [
                'manage_all_stores',
                'manage_own_store',
                'lock_day',
                'unlock_day',
                'lock_month',
                'unlock_month',
                'bypass_locked_day',
                'manage_staff',
                'view_payroll_all',
                'view_payroll_store',
                'config_kpi',
                'manage_permissions',
            ],
            'store_manager' => [
                'manage_own_store',
                'bypass_locked_day',
                'view_payroll_store',
                'manage_staff',
            ],
            'staff' => [],
        ];

        return $map[$role] ?? [];
    }
}
