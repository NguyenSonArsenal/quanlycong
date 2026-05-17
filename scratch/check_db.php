<?php
use App\Models\DailyTarget;
use App\Models\KpiConfig;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$config = KpiConfig::orderBy('id', 'desc')->first();
echo "Config ID: " . $config->id . "\n";
echo "Locked Weeks: " . json_serialize($config->locked_weeks) . "\n";

$targets = DailyTarget::where('kpi_config_id', $config->id)->orderBy('date')->get();
foreach ($targets as $t) {
    echo "Date: {$t->date} | Target: {$t->target_amount} | Rebalanced: {$t->rebalanced_target}\n";
}

function json_serialize($var) {
    return json_encode($var, JSON_UNESCAPED_UNICODE);
}
