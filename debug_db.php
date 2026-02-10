<?php
require_once 'config/database.php';

echo "=== TABLES ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "- $t\n";
}

echo "\n=== TIPOS_TRABAJOS COLUMNS ===\n";
$cols = $pdo->query("DESCRIBE tipos_trabajos")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")\n";
}

echo "\n=== DATA CHECK ===\n";
// Check if table 'especialidades' exists
if (in_array('especialidades', $tables)) {
    echo "Found table 'especialidades'!\n";
    $rows = $pdo->query("SELECT * FROM especialidades LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} else {
    echo "Table 'especialidades' NOT found.\n";
}

// Check if 'tipos_trabajo' (singular) still exists and what it has
if (in_array('tipos_trabajo', $tables)) {
    echo "\nFound table 'tipos_trabajo' (singular). Is this the one?\n";
    $rows = $pdo->query("SELECT * FROM tipos_trabajo LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
}

// Check 'tipologias'
if (in_array('tipologias', $tables)) {
    echo "\n=== TIPOLOGIAS TABLE ===\n";
    $cols = $pdo->query("DESCRIBE tipologias")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo $c['Field'] . " (" . $c['Type'] . ")\n";

    echo "\nDATA:\n";
    $rows = $pdo->query("SELECT * FROM tipologias LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
}
?>