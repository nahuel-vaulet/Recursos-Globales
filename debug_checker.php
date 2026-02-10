<?php
require_once 'config/database.php';

echo "=== DIAGNOSTICO DE TABLAS ===\n";

function print_columns($pdo, $table)
{
    echo "\nTabla: $table\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Error reading table $table: " . $e->getMessage() . "\n";
    }
}

print_columns($pdo, 'tareas_definicion');
print_columns($pdo, 'tareas_instancia');
print_columns($pdo, 'odt_maestro');

echo "\n=== TEST GENERATOR ===\n";
try {
    require_once 'includes/tasks_generator.php';
    echo "Include OK.\n";
    // We won't run generarTareasPendientes() fully to avoid modifying DB state blindly, 
    // but we can try to Select definitions to see if it targets existing columns.

    // Test the SELECT query from tasks_generator specifically
    echo "Testing SELECT query...\n";
    $sql = "SELECT * FROM tareas_definicion WHERE tipo_recurrencia != 'Unica'";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Rows found: " . count($rows) . "\n";
    if (count($rows) > 0) {
        $row = $rows[0];
        echo "Sample row keys: " . implode(", ", array_keys($row)) . "\n";

        if (isset($row['prioridad']))
            echo "Has 'prioridad': " . $row['prioridad'] . "\n";
        if (isset($row['importancia']))
            echo "Has 'importancia': " . $row['importancia'] . "\n";
    }

} catch (Exception $e) {
    echo "Generador Error: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>