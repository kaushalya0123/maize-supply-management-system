<?php
require_once 'config/db.php';

try {
    // 1. Add supply_date column if it doesn't exist
    $pdo->exec("ALTER TABLE maize ADD COLUMN supply_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER variety_id");
    echo "Column 'supply_date' added to 'maize' table successfully.\n";

} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'supply_date' already exists.\n";
    } else {
        echo "Migration Error: " . $e->getMessage() . "\n";
    }
}

try {
    // 2. Add created_at column to variety if it doesn't exist
    $pdo->exec("ALTER TABLE variety ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER price_per_kg");
    echo "Column 'created_at' added to 'variety' table successfully.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column 'created_at' already exists in 'variety'.\n";
    } else {
        echo "Migration Error: " . $e->getMessage() . "\n";
    }
}
?>