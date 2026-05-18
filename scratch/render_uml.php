<?php

$mermaidCode = <<<EOT
classDiagram
    class Store {
        +int id
        +string code
        +string name
        +int area_id
    }
    class User {
        +int id
        +string username
        +string full_name
        +string role
        +int store_id
        +int position_id
        +string contract_type
        +decimal hourly_rate
        +tinyint status
    }
    class Position {
        +int id
        +string code
        +string name
        +boolean is_sales
        +decimal default_hourly_rate
        +decimal team_bonus_base
    }
    class ShiftRecord {
        +int id
        +int store_id
        +int user_id
        +date date
        +string shift_type
        +decimal hours
        +decimal personal_revenue
        +boolean is_locked
    }
    class KpiConfig {
        +int id
        +int store_id
        +string month
        +bigint total_target
        +array locked_weeks
    }
    class DailyTarget {
        +int id
        +int kpi_config_id
        +date date
        +decimal target_amount
    }
    class EmployeeDailyKpi {
        +int id
        +int store_id
        +int user_id
        +date date
        +decimal target_amount
    }
    class CommissionBracket {
        +int id
        +string position_code
        +string contract_type
        +decimal min_kpi
        +decimal max_kpi
        +decimal commission_rate
    }
    Store "1" --> "*" User : contains
    Store "1" --> "*" ShiftRecord : contains
    Store "1" --> "*" KpiConfig : contains
    Position "1" --> "*" User : defines_rate
    Position "1" --> "*" CommissionBracket : defines_brackets
    User "1" --> "*" ShiftRecord : logs
    User "1" --> "*" EmployeeDailyKpi : tracks
    KpiConfig "1" --> "*" DailyTarget : distributes
EOT;

// Mermaid.ink expects base64 URL safe or standard base64 encoding of the JSON state
$jsonState = json_encode([
    'code' => $mermaidCode,
    'mermaid' => [
        'theme' => 'default'
    ]
]);

$base64 = base64_encode($jsonState);
// Make base64 URL-safe or simple
$url = "https://mermaid.ink/img/" . $base64;

echo "Fetching diagram from: $url\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    $dir = __DIR__ . '/../docs';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/class_diagram.png', $imageData);
    echo "Successfully generated docs/class_diagram.png\n";
} else {
    echo "Failed to fetch image. HTTP Code: $httpCode\n";
}
EOT;
