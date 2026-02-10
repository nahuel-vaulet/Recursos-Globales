<?php
/**
 * Script para insertar tipos de trabajo con tildes correctas
 */
require_once 'config/database.php';

// Limpiar tabla
$pdo->exec("DELETE FROM tipologias");
$pdo->exec("ALTER TABLE tipologias AUTO_INCREMENT = 1");

// Tipos de trabajo con tildes correctas
$tipos = [
    'Demolición y Corte',
    'Excavación y Relleno',
    'Reparación de Veredas',
    'Refacción de Calzada',
    'Instalación de Medidores',
    'Servicios de Agua',
    'Servicios de Cloaca',
    'Logística y Carga',
    'Seguridad y EPP',
    'Gestión Operativa',
    'Hormigonado y BR (Bocas de Registro)'
];

$stmt = $pdo->prepare("INSERT INTO tipologias (nombre) VALUES (?)");

foreach ($tipos as $tipo) {
    $stmt->execute([$tipo]);
    echo "Insertado: $tipo\n";
}

echo "\n✅ Tipos de trabajo insertados correctamente con tildes UTF-8\n";

// Verificar
echo "\nVerificación:\n";
$rows = $pdo->query("SELECT id_tipologia, nombre FROM tipologias ORDER BY id_tipologia")->fetchAll();
foreach ($rows as $r) {
    echo "  {$r['id_tipologia']}. {$r['nombre']}\n";
}
?>