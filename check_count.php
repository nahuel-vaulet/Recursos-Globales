<?php
require_once 'config/database.php';
try {
    $count = $pdo->query("SELECT COUNT(*) FROM tipos_trabajos")->fetchColumn();
    echo "Total Types in Global Table: " . $count . "\n";

    // List a few to verify
    $stmt = $pdo->query("SELECT * FROM tipos_trabajos LIMIT 5");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo $r['id_tipologia'] . ": " . $r['nombre'] . " [" . $r['codigo_trabajo'] . "]\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>