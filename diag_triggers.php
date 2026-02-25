<?php
require_once 'config/database.php';
try {
    echo "--- TRIGGERS ON ALL TABLES ---\n";
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($triggers as $t) {
        echo "Table: " . $t['Table'] . " | Name: " . $t['Trigger'] . " | Event: " . $t['Event'] . " | Statement: " . $t['Statement'] . "\n\n";
    }

    if (empty($triggers))
        echo "No triggers found.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>