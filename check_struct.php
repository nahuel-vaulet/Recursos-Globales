<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE tareas_instancia");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
