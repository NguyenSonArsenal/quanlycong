<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftRecord extends Model
{
    protected $fillable = [
        'user_id', 'store_id', 'date', 'shift_type', 'hours', 'shift_revenue', 'personal_revenue', 'kpi_percentage'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
