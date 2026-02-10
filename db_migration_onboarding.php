<?php
/**
 * Migración: Workflow de Ingreso y Documentación
 * Agrega campos para controlar el estado del legajo y archivos firmados.
 */
require_once 'config/database.php';

echo "Iniciando migración de tabla 'personal' (Workflow Onboarding)...\n";

try {
    $columns = $pdo->query("SHOW COLUMNS FROM personal")->fetchAll(PDO::FETCH_COLUMN);

    $colMap = [
        // Estado del flujo
        'estado_documentacion' => "ADD COLUMN estado_documentacion ENUM('Completo', 'Pendiente', 'Incompleto') DEFAULT 'Incompleto'",
        'motivo_pendiente' => "ADD COLUMN motivo_pendiente TEXT DEFAULT NULL",
        'responsable_carga_id' => "ADD COLUMN responsable_carga_id INT DEFAULT NULL", // ID del usuario que carga

        // Archivos y Fechas
        'documento_firmado' => "ADD COLUMN documento_firmado VARCHAR(255) DEFAULT NULL", // Ficha de ingreso firmada
        'fecha_firma_hys' => "ADD COLUMN fecha_firma_hys DATE DEFAULT NULL",

        // Preocupacional
        'fecha_examen_preocupacional' => "ADD COLUMN fecha_examen_preocupacional DATE DEFAULT NULL",
        'empresa_examen_preocupacional' => "ADD COLUMN empresa_examen_preocupacional VARCHAR(100) DEFAULT NULL",
        'documento_preocupacional' => "ADD COLUMN documento_preocupacional VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($colMap as $colName => $sqlAdd) {
        if (!in_array($colName, $columns)) {
            echo "Agregando columna: $colName\n";
            $pdo->exec("ALTER TABLE personal $sqlAdd");
        } else {
            echo "Columna ya existe: $colName (Saltando)\n";
        }
    }

    echo "Migración completada con éxito.\n";

} catch (PDOException $e) {
    die("Error en migración: " . $e->getMessage());
}
