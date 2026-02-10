<?php
require_once 'config/database.php';

try {
    echo "Actualizando esquema de base de datos...\n";

    // Agregamos 'En Curso' al ENUM
    // Nota: Es importante listar todos los valores antiguos + el nuevo
    $sql = "ALTER TABLE tareas_instancia 
            MODIFY COLUMN estado ENUM('Pendiente', 'En Curso', 'Completada', 'Cancelada') DEFAULT 'Pendiente'";

    echo "Ejecutando ALTER TABLE...\n";
    $pdo->exec($sql);
    echo "¡Columna 'estado' actualizada correctamente!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>