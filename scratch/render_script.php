<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\KpiController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

$user = User::where('role', 'admin')->first() ?: User::first();
Auth::login($user);

$controller = new KpiController();
$response = $controller->show(51);
$html = $response->render();

// Extract the script tag content starting from the second script block
if (preg_match_all('/<script>(.*?)<\/script>/s', $html, $matches)) {
    foreach ($matches[1] as $script) {
        if (strpos($script, 'LOCKED_WEEKS') !== false) {
            echo "=== FIRST 20 LINES OF SCRIPT ===\n";
            $lines = explode("\n", $script);
            echo implode("\n", array_slice($lines, 0, 20));
            echo "\n====================\n";
        }
    }
} else {
    echo "No script tag found\n";
}
