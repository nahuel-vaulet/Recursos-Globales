<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE personal");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "--- DESCRIBE PERSONAL START ---\n";
print_r($data);
echo "--- DESCRIBE PERSONAL END ---\n";
?>