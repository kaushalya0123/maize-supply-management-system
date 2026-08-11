<?php
// deduplicate_varieties.php
require_once 'config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Clean variety names (trim trailing/leading spaces)
    $stmt = $pdo->query("SELECT * FROM variety");
    $allVarieties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateNameStmt = $pdo->prepare("UPDATE variety SET type = ? WHERE variety_id = ?");
    foreach ($allVarieties as $v) {
        $trimmed = trim($v['type']);
        if ($trimmed !== $v['type']) {
            $updateNameStmt->execute([$trimmed, $v['variety_id']]);
            echo "Trimmed variety ID {$v['variety_id']}: '{$v['type']}' -> '$trimmed'\n";
        }
    }

    // 2. Fetch all varieties again to find case-insensitive duplicates
    $stmt = $pdo->query("SELECT * FROM variety");
    $allVarieties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($allVarieties as $v) {
        $key = strtolower($v['type']);
        $groups[$key][] = $v['variety_id'];
    }

    // 3. For each group with more than 1 variety_id, merge duplicates
    foreach ($groups as $typeName => $ids) {
        if (count($ids) > 1) {
            $keepId = min($ids); // Keep the earliest (smallest ID)
            $duplicateIds = array_diff($ids, [$keepId]);
            $dupListStr = implode(',', $duplicateIds);

            echo "Merging duplicates for variety type '$typeName': keeping ID $keepId, removing IDs: $dupListStr\n";

            // Update referencing tables:
            // a) maize
            $countMaize = $pdo->exec("UPDATE maize SET variety_id = $keepId WHERE variety_id IN ($dupListStr)");
            echo "  Updated $countMaize records in 'maize' table.\n";

            // b) orders
            $countOrders = $pdo->exec("UPDATE orders SET variety_id = $keepId WHERE variety_id IN ($dupListStr)");
            echo "  Updated $countOrders records in 'orders' table.\n";

            // c) stock
            $countStock = $pdo->exec("UPDATE stock SET variety_id = $keepId WHERE variety_id IN ($dupListStr)");
            echo "  Updated $countStock records in 'stock' table.\n";

            // d) Delete the duplicates from variety table
            $countDel = $pdo->exec("DELETE FROM variety WHERE variety_id IN ($dupListStr)");
            echo "  Deleted $countDel duplicate records from 'variety' table.\n";
        }
    }

    // 4. Check if the unique constraint already exists, if not, add it
    $hasUniqueConstraint = false;
    $stmt = $pdo->query("SHOW KEYS FROM variety WHERE Key_name = 'unique_variety_type'");
    if ($stmt->fetch()) {
        $hasUniqueConstraint = true;
    }

    if (!$hasUniqueConstraint) {
        $pdo->exec("ALTER TABLE variety ADD CONSTRAINT unique_variety_type UNIQUE (type)");
        echo "Added UNIQUE constraint 'unique_variety_type' to variety(type).\n";
    } else {
        echo "UNIQUE constraint 'unique_variety_type' already exists on variety(type).\n";
    }

    $pdo->commit();
    echo "Deduplication and unique constraint setup completed successfully.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Deduplication Error: " . $e->getMessage() . "\n");
}
?>