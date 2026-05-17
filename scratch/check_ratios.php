<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\KpiConfig;

$config = KpiConfig::orderBy('id', 'desc')->first();
echo "Config ID: " . $config->id . "\n";
echo "Daily Ratios: " . json_encode($config->daily_ratios) . "\n";
echo "Weekly Ratios: " . json_encode($config->weekly_ratios) . "\n";
