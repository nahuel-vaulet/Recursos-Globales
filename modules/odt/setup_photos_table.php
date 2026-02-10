<?php
require_once '../../config/database.php';

try {
    $sql = file_get_contents('../../sql/create_odt_fotos.sql');
    $pdo->exec($sql);
    echo "Table 'odt_fotos' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
