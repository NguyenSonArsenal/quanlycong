<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTarget extends Model
{
    protected $fillable = [
        'kpi_config_id', 'date', 'week_number', 'target_amount', 'rebalanced_target'
    ];

    public function kpiConfig()
    {
        return $this->belongsTo(KpiConfig::class);
    }
}
