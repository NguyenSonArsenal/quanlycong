<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
