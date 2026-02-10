<?php
/**
 * Migración: Actualización Tabla Personal
 * Agrega nuevos campos para legajo digital, EPP, salud y familia
 */
require_once 'config/database.php';

echo "Iniciando migración de tabla 'personal'...\n";

try {
    // 1. Verificar columnas existentes para no duplicar error
    $columns = $pdo->query("SHOW COLUMNS FROM personal")->fetchAll(PDO::FETCH_COLUMN);

    $newColumns = [
        "ADD COLUMN tiene_carnet TINYINT(1) DEFAULT 0",
        "ADD COLUMN tipo_carnet VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN foto_carnet VARCHAR(255) DEFAULT NULL",
        "ADD COLUMN fecha_nacimiento DATE DEFAULT NULL",
        "ADD COLUMN cuil VARCHAR(20) DEFAULT NULL",
        "ADD COLUMN estado_civil VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN contacto_emergencia_nombre VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN contacto_emergencia_parentesco VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN personas_a_cargo TEXT DEFAULT NULL",
        "ADD COLUMN foto_usuario VARCHAR(255) DEFAULT NULL",
        "ADD COLUMN talle_camisa VARCHAR(10) DEFAULT NULL",
        "ADD COLUMN talle_pantalon VARCHAR(10) DEFAULT NULL",
        "ADD COLUMN talle_remera VARCHAR(10) DEFAULT NULL",
        // fecha_ultima_entrega_epp ya existe en el form anterior, verificamos si existe en DB
        // Planilla EPP
        "ADD COLUMN planilla_epp VARCHAR(255) DEFAULT NULL",
        "ADD COLUMN tareas_desempenadas TEXT DEFAULT NULL",
        "ADD COLUMN obra_social VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN obra_social_telefono VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN obra_social_lugar_atencion VARCHAR(200) DEFAULT NULL"
    ];

    // Verificar si ya existen antes de agregar
    // Mapeo simple de nombre columna -> definición
    $colMap = [
        'tiene_carnet' => "ADD COLUMN tiene_carnet TINYINT(1) DEFAULT 0",
        'tipo_carnet' => "ADD COLUMN tipo_carnet VARCHAR(50) DEFAULT NULL",
        'foto_carnet' => "ADD COLUMN foto_carnet VARCHAR(255) DEFAULT NULL",
        'fecha_nacimiento' => "ADD COLUMN fecha_nacimiento DATE DEFAULT NULL",
        'cuil' => "ADD COLUMN cuil VARCHAR(20) DEFAULT NULL",
        'estado_civil' => "ADD COLUMN estado_civil VARCHAR(50) DEFAULT NULL",
        'contacto_emergencia_nombre' => "ADD COLUMN contacto_emergencia_nombre VARCHAR(100) DEFAULT NULL",
        'contacto_emergencia_parentesco' => "ADD COLUMN contacto_emergencia_parentesco VARCHAR(50) DEFAULT NULL",
        'personas_a_cargo' => "ADD COLUMN personas_a_cargo TEXT DEFAULT NULL",
        'foto_usuario' => "ADD COLUMN foto_usuario VARCHAR(255) DEFAULT NULL",
        'talle_camisa' => "ADD COLUMN talle_camisa VARCHAR(10) DEFAULT NULL",
        'talle_pantalon' => "ADD COLUMN talle_pantalon VARCHAR(10) DEFAULT NULL",
        'talle_remera' => "ADD COLUMN talle_remera VARCHAR(10) DEFAULT NULL",
        'planilla_epp' => "ADD COLUMN planilla_epp VARCHAR(255) DEFAULT NULL",
        'tareas_desempenadas' => "ADD COLUMN tareas_desempenadas TEXT DEFAULT NULL",
        'obra_social' => "ADD COLUMN obra_social VARCHAR(100) DEFAULT NULL",
        'obra_social_telefono' => "ADD COLUMN obra_social_telefono VARCHAR(50) DEFAULT NULL",
        'obra_social_lugar_atencion' => "ADD COLUMN obra_social_lugar_atencion VARCHAR(200) DEFAULT NULL"
    ];

    foreach ($colMap as $colName => $sqlAdd) {
        if (!in_array($colName, $columns)) {
            echo "Agregando columna: $colName\n";
            $pdo->exec("ALTER TABLE personal $sqlAdd");
        } else {
            echo "Columna ya existe: $colName (Saltando)\n";
        }
    }

    // Actualizar ENUM de rol para quitar 'Chofer' si es posible, o simplemente dejar de usarlo en el frontend.
    // Modificar un ENUM en MySQL puede ser delicado si hay datos. 
    // Por seguridad, NO tocaremos el ENUM de BD, solo filtraremos en el Frontend.
    // El usuario pidió: "QUITAR ROL CHOFER". Lo haremos a nivel UI.

    echo "Migración completada con éxito.\n";

} catch (PDOException $e) {
    die("Error en migración: " . $e->getMessage());
}
