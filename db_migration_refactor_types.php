<?php
require_once 'config/database.php';

echo "<h2>Migrando a Tabla Global de Tipos de Trabajo</h2>";

try {
    $pdo->beginTransaction();

    // 1. Drop the intermediate table if exists (to recreate with correct FK)
    $pdo->exec("DROP TABLE IF EXISTS cuadrilla_tipos_trabajo");
    echo "Tabla intermedia anterior eliminada.<br>";

    // 2. Recreate intermediate table with correct Foreign Keys
    // Link to 'tipos_trabajos' (id_tipologia) instead of 'tipos_trabajo' (id_tipo)
    $pdo->exec("CREATE TABLE cuadrilla_tipos_trabajo (
        id_cuadrilla INT NOT NULL,
        id_tipologia INT NOT NULL,
        PRIMARY KEY (id_cuadrilla, id_tipologia),
        FOREIGN KEY (id_tipologia) REFERENCES tipos_trabajos(id_tipologia) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Nueva tabla intermedia 'cuadrilla_tipos_trabajo' creada (vinculada a id_tipologia).<br>";

    // 3. Drop the redundant table I created earlier
    $pdo->exec("DROP TABLE IF EXISTS tipos_trabajo");
    echo "Tabla redundante 'tipos_trabajo' eliminada.<br>";

    $pdo->commit();
    echo "<h3 style='color:green'>Refactorización de Schema Completada.</h3>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>