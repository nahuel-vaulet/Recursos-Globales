<?php
require_once 'config/database.php';

echo "<h1>Debug Urgent Tasks</h1>";
echo "Current Date: " . date('Y-m-d H:i:s') . "<br>";

// 1. Check ODTs
echo "<h2>ODT Query</h2>";
$sql_odt = "SELECT 
            o.id_odt as id, 
            o.estado_gestion,
            p.fecha_programada
        FROM odt_maestro o 
        LEFT JOIN programacion_semanal p ON o.id_odt = p.id_odt 
        WHERE o.estado_gestion = 'Programado' 
        AND p.fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

echo "<pre>$sql_odt</pre>";
try {
    $stmt = $pdo->query($sql_odt);
    $odts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($odts) . "<br>";
    print_r($odts);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 2. Check Tasks
echo "<h2>Tasks Query</h2>";
$sql_tasks = "SELECT 
            id_tarea,
            titulo,
            estado,
            fecha_vencimiento
          FROM tareas_instancia
          WHERE estado IN ('Pendiente', 'En Curso')
          AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

echo "<pre>$sql_tasks</pre>";
try {
    $stmt = $pdo->query($sql_tasks);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($tasks) . "<br>";
    print_r($tasks);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 3. Raw Tasks Dump for Today (Broad)
echo "<h2>All Tasks for Today (Broad Check)</h2>";
$sql_broad = "SELECT * FROM tareas_instancia WHERE fecha_vencimiento = CURDATE()";
$stmt = $pdo->query($sql_broad);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>