<?php
require_once 'config/database.php';

try {
    echo "Iniciando migración para agregar estado 'Postergado'...\n";

    // 1. Verificar si el estado ya existe en el ENUM (aproximación)
    // Para simplificar, ejecutaremos el ALTER TABLE directamente. 
    // Si ya existe, MySQL suele manejarlo o lanzar advertencia, pero aseguramos la lista completa.

    $sql = "ALTER TABLE odt_maestro 
            MODIFY COLUMN estado_gestion 
            ENUM('Sin Programar', 'Programación Solicitada', 'Programado', 'Ejecución', 'Ejecutado', 'Precertificada', 'Aprobado por inspector', 'Retrabajo', 'Finalizado', 'Postergado') 
            DEFAULT 'Sin Programar'";

    $pdo->exec($sql);
    echo "[OK] Columna 'estado_gestion' actualizada correctamente con 'Postergado'.\n";

} catch (PDOException $e) {
    echo "[ERROR] Falló la migración: " . $e->getMessage() . "\n";
}
?>