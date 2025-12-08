<?php
chdir('/var/www/html/radius-gui');
require 'bootstrap.php';

echo "Verifying VLAN column in Auth Log:\n";
echo "====================================\n\n";

$db = Database::getInstance();

// Test 1: Check if vlan column exists
echo "Test 1: Query radpostauth with VLAN column\n";
try {
    $result = $db->fetchAll("SELECT id, username, reply, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 5");
    echo "✓ SUCCESS: VLAN column query works!\n";
    echo "Records found: " . count($result) . "\n\n";

    if (count($result) > 0) {
        echo "Sample records:\n";
        foreach ($result as $row) {
            echo "  - User: {$row['username']}, VLAN: " . ($row['vlan'] ?? 'NULL') . ", Reply: {$row['reply']}\n";
        }
    } else {
        echo "  No authentication records yet (this is expected for new deployment)\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Verify Auth Log controller can load
echo "Test 2: Auth Log Controller\n";
try {
    require_once APP_PATH . '/controllers/AuthLogController.php';
    echo "✓ SUCCESS: AuthLogController loaded\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Verify Reports controller can load
echo "Test 3: Reports Controller\n";
try {
    require_once APP_PATH . '/controllers/ReportsController.php';
    echo "✓ SUCCESS: ReportsController loaded\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n✓ All verification tests completed!\n";
