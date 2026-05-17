<?php
use App\Models\DailyTarget;
use App\Models\KpiConfig;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$config = KpiConfig::orderBy('id', 'desc')->first();
$id = $config->id;

$targets = [
    '2026-05-11' => 3529411.76,
    '2026-05-12' => 3569333.33,
    '2026-05-13' => 3395384.62,
    '2026-05-14' => 2536363.64,
    '2026-05-15' => 2853333.33,
    '2026-05-16' => 3430000.00,
    '2026-05-17' => 4560000.00,
];

echo "Restoring daily rebalanced targets for Week 3...\n";
foreach ($targets as $date => $target) {
    DailyTarget::where('kpi_config_id', $id)
        ->where('date', $date)
        ->update(['rebalanced_target' => $target]);
    echo "Date: {$date} | Restored Target: " . number_format($target, 2) . "\n";
}

echo "Done!\n";
