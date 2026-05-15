<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'is_sales', 'team_bonus_base'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
