<?php
require_once 'config/database.php';

function d($p, $t)
{
    try {
        echo "\nTABLE: $t\n";
        $s = $p->query("DESCRIBE $t");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $c) {
            echo str_pad($c['Field'], 25) . " " . $c['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "$t ERR: " . $e->getMessage() . "\n";
    }
}

d($pdo, 'odt_maestro');
d($pdo, 'odt_fotos');
?>