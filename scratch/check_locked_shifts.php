<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tot = \App\Models\ShiftRecord::where('store_id', 1)->where('date', 'like', '2026-05%')->count();
$lock = \App\Models\ShiftRecord::where('store_id', 1)->where('date', 'like', '2026-05%')->where('is_locked', true)->count();
echo "Total: " . $tot . " | Locked: " . $lock . "\n";
