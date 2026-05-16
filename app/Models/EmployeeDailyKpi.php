<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDailyKpi extends Model
{
    protected $table = 'employee_daily_kpi';

    protected $fillable = [
        'user_id', 'store_id', 'date',
        'target_amount', 'kpi_percentage', 'total_personal_revenue',
        'customers', 'fitting_rooms', 'orders', 'products',
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
