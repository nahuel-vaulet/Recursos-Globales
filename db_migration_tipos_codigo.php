<?php
require_once 'config/database.php';

try {
    // Add column if not exists
    $columns = $pdo->query("DESCRIBE tipos_trabajo")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('codigo', $columns)) {
        $pdo->exec("ALTER TABLE tipos_trabajo ADD COLUMN codigo VARCHAR(10) AFTER id_tipo");
        echo "Columna 'codigo' agregada.<br>";
    }

    // Update defaults
    $updates = [
        'Veredas' => '3.1',
        'Hidráulica' => '4.0',
        'Medidores' => '2.5',
        'Calzada' => '3.2',
        'Emergencias' => '9.0',
        'Poda' => '5.0',
        'Zanjeo' => '4.1'
    ];

    $stmt = $pdo->prepare("UPDATE tipos_trabajo SET codigo = ? WHERE descripcion = ?");
    foreach ($updates as $desc => $code) {
        $stmt->execute([$code, $desc]);
    }
    echo "Códigos actualizados.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>