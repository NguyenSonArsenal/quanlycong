<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'area_id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
