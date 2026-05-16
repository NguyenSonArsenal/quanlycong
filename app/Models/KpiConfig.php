<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'month',
        'total_target',
        'weekly_ratios',
        'daily_ratios',
        'shift_ratios_weekday',
        'shift_ratios_weekend',
    ];

    protected $casts = [
        'weekly_ratios'        => 'array',
        'daily_ratios'         => 'array',
        'shift_ratios_weekday' => 'array',
        'shift_ratios_weekend' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function dailyTargets()
    {
        return $this->hasMany(DailyTarget::class);
    }

    /**
     * Lấy tỷ trọng ca cho một ngày cụ thể (weekday vs weekend)
     */
    public function getShiftRatioForDate(string $date): array
    {
        $isWeekend = \Carbon\Carbon::parse($date)->isoWeekday() >= 6;
        $default = $isWeekend
            ? ['morning' => 12, 'afternoon' => 45, 'evening' => 43]
            : ['morning' => 10, 'afternoon' => 36, 'evening' => 54];
        return $isWeekend
            ? ($this->shift_ratios_weekend ?? $default)
            : ($this->shift_ratios_weekday ?? $default);
    }
}
