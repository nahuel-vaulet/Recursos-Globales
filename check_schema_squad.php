<?php
require_once 'config/database.php';
$tables = ['vehiculos', 'personal', 'herramientas', 'odt_maestro'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt = $pdo->query("DESCRIBE $t");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}
?>