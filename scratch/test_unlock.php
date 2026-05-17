<?php
use App\Models\DailyTarget;
use App\Models\KpiConfig;
use App\Http\Controllers\KpiController;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$config = KpiConfig::orderBy('id', 'desc')->first();
echo "--- UNLOCKING WEEK 3 ---\n";
$config->locked_weeks = [];
$config->save();

// Khởi tạo controller để chạy test
$controller = new KpiController();

// Dùng Reflection để gọi phương thức private rebalanceAllWeeks
$reflector = new ReflectionClass(KpiController::class);
$method = $reflector->getMethod('rebalanceAllWeeks');
$method->setAccessible(true);

echo "Running rebalanceAllWeeks...\n";
$method->invoke($controller, $config);

echo "\n--- AFTER UNLOCKING & REBALANCING ---\n";
$targets = DailyTarget::where('kpi_config_id', $config->id)->orderBy('date')->get();
foreach ($targets as $t) {
    echo "Date: {$t->date} | Target: " . number_format($t->target_amount, 2) . " | Rebalanced: " . number_format($t->rebalanced_target, 2) . "\n";
}
