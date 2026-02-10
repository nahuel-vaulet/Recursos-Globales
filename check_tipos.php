<?php
require_once 'config/database.php';
$s = $pdo->query('DESCRIBE tipos_trabajo');
foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . "\n";
}
?>