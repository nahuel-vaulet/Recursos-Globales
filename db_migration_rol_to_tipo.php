<?php
/**
 * Migration Script: Rename 'rol' column to 'tipo_usuario' in 'usuarios' table.
 */

require_once 'config/database.php';

echo "Starting migration...\n";

try {
    // 1. Check if 'rol' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
    $rolExists = $stmt->rowCount() > 0;

    // 2. Check if 'tipo_usuario' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'tipo_usuario'");
    $tipoExists = $stmt->rowCount() > 0;

    if ($rolExists && !$tipoExists) {
        echo "Renaming 'rol' to 'tipo_usuario'...\n";
        // Rename column keeping the same ENUM definition (adjusting if necessary based on current schema)
        // We use the definition found in login.php auto-setup but changing the name.
        $sql = "ALTER TABLE usuarios CHANGE COLUMN rol tipo_usuario ENUM('Gerente', 'Administrativo', 'JefeCuadrilla', 'Coordinador ASSA', 'Administrativo ASSA', 'Inspector ASSA') NOT NULL DEFAULT 'Administrativo'";
        $pdo->exec($sql);
        echo "Column renamed successfully.\n";
    } elseif ($tipoExists) {
        echo "Column 'tipo_usuario' already exists. Skipping rename.\n";
    } else {
        echo "Column 'rol' does not exist. Please check the schema manually.\n";
    }

    // 3. Update any potential index or constraint if needed (usually handled by ALTER)

    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>