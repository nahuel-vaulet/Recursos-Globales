<?php
/**
 * Módulo: Tipos de Trabajos
 * Endpoint para guardar (crear/actualizar)
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Recoger datos del formulario
$id = $_POST['id_tipologia'] ?? null;
$codigo_trabajo = trim($_POST['codigo_trabajo']);
$nombre = trim($_POST['nombre']);
$descripcion_breve = trim($_POST['descripcion_breve'] ?? '');
$descripcion_larga = trim($_POST['descripcion_larga'] ?? '');
$unidad_medida = $_POST['unidad_medida'];
$tiempo_limite_dias = !empty($_POST['tiempo_limite_dias']) ? (int) $_POST['tiempo_limite_dias'] : null;
$precio_unitario = !empty($_POST['precio_unitario']) ? (float) $_POST['precio_unitario'] : null;
$estado = isset($_POST['estado']) ? 1 : 0;

// Validaciones básicas
if (empty($codigo_trabajo) || empty($nombre) || empty($unidad_medida)) {
    header("Location: form.php" . ($id ? "?id=$id" : "") . "&msg=error");
    exit();
}

try {
    // Verificar código único (excepto para el registro actual)
    $checkSql = "SELECT id_tipologia FROM tipos_trabajos WHERE codigo_trabajo = ?";
    $checkParams = [$codigo_trabajo];
    if ($id) {
        $checkSql .= " AND id_tipologia != ?";
        $checkParams[] = $id;
    }
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($checkParams);

    if ($checkStmt->fetch()) {
        // Código duplicado
        header("Location: form.php" . ($id ? "?id=$id" : "") . "&msg=duplicate");
        exit();
    }

    if ($id) {
        // UPDATE
        $sql = "UPDATE tipos_trabajos SET 
                codigo_trabajo = ?, 
                nombre = ?, 
                descripcion_breve = ?, 
                descripcion_larga = ?, 
                unidad_medida = ?, 
                tiempo_limite_dias = ?, 
                precio_unitario = ?, 
                estado = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id_tipologia = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $codigo_trabajo,
            $nombre,
            $descripcion_breve ?: null,
            $descripcion_larga ?: null,
            $unidad_medida,
            $tiempo_limite_dias,
            $precio_unitario,
            $estado,
            $id
        ]);

        // Auditoría
        registrarAccion('EDITAR', 'tipos_trabajos', "Tipo de trabajo editado: [$codigo_trabajo] $nombre", $id);

    } else {
        // INSERT
        $sql = "INSERT INTO tipos_trabajos 
                (codigo_trabajo, nombre, descripcion_breve, descripcion_larga, unidad_medida, tiempo_limite_dias, precio_unitario, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $codigo_trabajo,
            $nombre,
            $descripcion_breve ?: null,
            $descripcion_larga ?: null,
            $unidad_medida,
            $tiempo_limite_dias,
            $precio_unitario,
            $estado
        ]);

        $newId = $pdo->lastInsertId();

        // Auditoría
        registrarAccion('CREAR', 'tipos_trabajos', "Tipo de trabajo creado: [$codigo_trabajo] $nombre", $newId);
    }

    header("Location: index.php?msg=saved");
    exit();

} catch (PDOException $e) {
    error_log("Error en tipos_trabajos/save.php: " . $e->getMessage());
    header("Location: index.php?msg=error");
    exit();
}
?>