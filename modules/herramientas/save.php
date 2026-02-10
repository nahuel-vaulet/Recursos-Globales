<?php
/**
 * Módulo Herramientas - Guardar
 * [!] ARQUITECTURA: Procesa CREATE y UPDATE con validación
 * [✓] AUDITORÍA CRUD: Registra acciones y movimientos
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $isEdit = !empty($_POST['id_herramienta']);

    // Procesar foto si se subió
    $foto = $_POST['foto_actual'] ?? '';
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/herramientas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowedExts)) {
            $filename = 'herramienta_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
                $foto = '/APP-Prueba/uploads/herramientas/' . $filename;
            }
        }
    }

    // [→] EDITAR AQUÍ: Campos del formulario
    $data = [
        'nombre' => trim($_POST['nombre']),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'numero_serie' => trim($_POST['numero_serie'] ?? ''),
        'marca' => trim($_POST['marca'] ?? ''),
        'modelo' => trim($_POST['modelo'] ?? ''),
        'precio_reposicion' => floatval($_POST['precio_reposicion'] ?? 0),
        'id_proveedor' => !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null,
        'foto' => $foto ?: null,
        'estado' => $_POST['estado'] ?? 'Disponible',
        'fecha_compra' => !empty($_POST['fecha_compra']) ? $_POST['fecha_compra'] : null,
        'fecha_calibracion' => !empty($_POST['fecha_calibracion']) ? $_POST['fecha_calibracion'] : null
    ];

    if ($isEdit) {
        $id_herramienta = $_POST['id_herramienta'];
        $sql = "UPDATE herramientas SET 
                nombre = :nombre,
                descripcion = :descripcion,
                numero_serie = :numero_serie,
                marca = :marca,
                modelo = :modelo,
                precio_reposicion = :precio_reposicion,
                id_proveedor = :id_proveedor,
                foto = :foto,
                estado = :estado,
                fecha_compra = :fecha_compra,
                fecha_calibracion = :fecha_calibracion
                WHERE id_herramienta = :id_herramienta";
        $data['id_herramienta'] = $id_herramienta;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        registrarAccion('EDITAR', 'herramientas', "Herramienta editada: " . $data['nombre'], $id_herramienta);
    } else {
        $sql = "INSERT INTO herramientas (
                    nombre, descripcion, numero_serie, marca, modelo,
                    precio_reposicion, id_proveedor, foto, estado, fecha_compra, fecha_calibracion
                ) VALUES (
                    :nombre, :descripcion, :numero_serie, :marca, :modelo,
                    :precio_reposicion, :id_proveedor, :foto, :estado, :fecha_compra, :fecha_calibracion
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $id_herramienta = $pdo->lastInsertId();

        // Registrar movimiento de compra si tiene fecha
        if (!empty($data['fecha_compra'])) {
            $stmtMov = $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, monto, observaciones, created_by) VALUES (?, 'Compra', ?, 'Alta de herramienta', ?)");
            $stmtMov->execute([$id_herramienta, $data['precio_reposicion'], $_SESSION['user_id'] ?? null]);
        }

        registrarAccion('CREAR', 'herramientas', "Herramienta creada: " . $data['nombre'], $id_herramienta);
    }

    $pdo->commit();
    header("Location: index.php?msg=success");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = urlencode($e->getMessage());
    $redirect = !empty($_POST['id_herramienta']) ? "form.php?id=" . $_POST['id_herramienta'] : "form.php";
    header("Location: $redirect&msg=error&details=$error");
}
?>