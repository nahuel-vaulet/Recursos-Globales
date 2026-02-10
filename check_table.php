<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("DESCRIBE tareas_instancia");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
