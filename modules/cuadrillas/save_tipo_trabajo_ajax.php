<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos
$nombre = trim($_POST['nombre'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');

if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
    exit;
}

try {
    // 1. Verificar duplicados (por nombre o código si existe)
    $sqlCheck = "SELECT COUNT(*) FROM tipologias WHERE nombre = ?";
    $params = [$nombre];

    if (!empty($codigo)) {
        $sqlCheck .= " OR codigo_trabajo = ?";
        $params[] = $codigo;
    }

    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute($params);

    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una especialidad con ese nombre o código']);
        exit;
    }

    // 2. Insertar
    $stmt = $pdo->prepare("INSERT INTO tipologias (nombre, codigo_trabajo) VALUES (?, ?)");
    $stmt->execute([$nombre, !empty($codigo) ? $codigo : null]);
    $id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'data' => [
            'id_tipologia' => $id,
            'nombre' => $nombre,
            'codigo_trabajo' => $codigo
        ]
    ]);

} catch (PDOException $e) {
    // Log error internally if needed
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
