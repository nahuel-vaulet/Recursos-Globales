<?php
require_once 'config/database.php';

try {
    echo "Iniciando migración de Prioridad a Importancia...\n";

    // 1. TAREAS_DEFINICION
    // Cambiar a VARCHAR temporalmente para permitir manipulación
    $pdo->exec("ALTER TABLE tareas_definicion MODIFY prioridad VARCHAR(20)");
    echo "- Tareas Definicion: Columna convertida a VARCHAR\n";

    // Actualizar valores
    $pdo->exec("UPDATE tareas_definicion SET prioridad = 'Alta' WHERE prioridad = 'Urgente'");
    $pdo->exec("UPDATE tareas_definicion SET prioridad = 'Baja' WHERE prioridad = 'Normal' OR prioridad IS NULL"); // Default to Baja
    echo "- Tareas Definicion: Valores actualizados (Urgente->Alta, Normal->Baja)\n";

    // Renombrar y convertir a ENUM final
    $pdo->exec("ALTER TABLE tareas_definicion CHANGE prioridad importancia ENUM('Alta', 'Baja') DEFAULT 'Baja'");
    echo "- Tareas Definicion: Columna renombrada a 'importancia' ENUM('Alta', 'Baja')\n";


    // 2. TAREAS_INSTANCIA
    // Cambiar a VARCHAR temporalmente
    $pdo->exec("ALTER TABLE tareas_instancia MODIFY prioridad VARCHAR(20)");
    echo "- Tareas Instancia: Columna convertida a VARCHAR\n";

    // Actualizar valores
    $pdo->exec("UPDATE tareas_instancia SET prioridad = 'Alta' WHERE prioridad = 'Urgente'");
    $pdo->exec("UPDATE tareas_instancia SET prioridad = 'Baja' WHERE prioridad = 'Normal' OR prioridad IS NULL");
    echo "- Tareas Instancia: Valores actualizados\n";

    // Renombrar y convertir a ENUM final
    $pdo->exec("ALTER TABLE tareas_instancia CHANGE prioridad importancia ENUM('Alta', 'Baja') DEFAULT 'Baja'");
    echo "- Tareas Instancia: Columna renombrada a 'importancia'\n";

    echo "Migración COMPLETADA con éxito.";

} catch (PDOException $e) {
    die("ERROR DE MIGRACIÓN: " . $e->getMessage());
}
?>