<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id_tipologia, nombre FROM tipos_trabajos ORDER BY nombre");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($types as $t) {
    echo "ID: {$t['id_tipologia']} - Name: {$t['nombre']}\n";
}
?>