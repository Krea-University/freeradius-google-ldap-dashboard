<?php
require '/var/www/html/radius-gui/bootstrap.php';

echo "Testing Auth Log query with VLAN column:\n";
echo "==========================================\n\n";

$db = Database::getInstance();

$sql = "SELECT
    id,
    username,
    reply,
    vlan,
    authdate
FROM radpostauth
ORDER BY authdate DESC
LIMIT 5";

try {
    $logs = $db->fetchAll($sql);
    echo "✓ Query executed successfully!\n";
    echo "Records found: " . count($logs) . "\n\n";

    if (count($logs) > 0) {
        echo "Recent authentication logs:\n";
        echo "----------------------------\n";
        foreach ($logs as $log) {
            echo "ID: " . $log['id'] . "\n";
            echo "  Username: " . $log['username'] . "\n";
            echo "  Reply: " . $log['reply'] . "\n";
            echo "  VLAN: " . ($log['vlan'] ?? 'NULL') . "\n";
            echo "  Date: " . $log['authdate'] . "\n";
            echo "\n";
        }
    } else {
        echo "No authentication logs found yet.\n";
        echo "This is expected if no authentications have occurred since deployment.\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
