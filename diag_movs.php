<?php
require_once 'config/database.php';
try {
    echo "--- LAST 10 MOVEMENTS ---\n";
    $stmt = $pdo->query("SELECT * FROM movimientos ORDER BY id_movimiento DESC LIMIT 10");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>