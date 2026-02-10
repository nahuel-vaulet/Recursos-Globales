<?php
require_once 'config/database.php';
header('Content-Type: text/plain');

try {
    echo "Testing Dashboard SQL...\n";

    // Replicating the logic from get_dashboard_kpis.php

    // Parte 1: ODTs
    $sql_odt = "SELECT 
                o.id_odt as id, 
                CONCAT('ODT ', o.nro_odt_assa) as titulo, 
                o.direccion as descripcion, 
                t.nombre as tipo, 
                'ODT' as source,
                1 as is_urgent,
                o.estado_gestion as estado
            FROM odt_maestro o 
            LEFT JOIN programacion_semanal p ON o.id_odt = p.id_odt 
            LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia 
            WHERE o.prioridad = 'Urgente' 
            AND o.estado_gestion = 'Programado' 
            AND p.fecha_programada = CURDATE()";

    // Parte 2: Tareas de Agenda (Urgentes o En Curso Vencidas)
    $sql_tasks = "SELECT 
                id_tarea as id,
                titulo,
                descripcion,
                'Tarea' as tipo,
                'AGENDA' as source,
                CASE WHEN importancia = 'Alta' THEN 1 ELSE 0 END as is_urgent,
                estado
              FROM tareas_instancia
              WHERE (estado = 'En Curso')
                 OR (importancia = 'Alta' AND estado = 'Pendiente' AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 DAY))";

    $sql_union = "($sql_odt) UNION ALL ($sql_tasks) ORDER BY is_urgent DESC, id ASC";

    echo "Query:\n$sql_union\n\n";

    $stmt = $pdo->query($sql_union);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Success! Found " . count($results) . " rows.\n";
    print_r($results);

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
