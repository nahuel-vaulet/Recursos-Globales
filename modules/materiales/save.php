<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_material'] ?? null;
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $unidad_medida = $_POST['unidad_medida'];
    $punto_pedido = !empty($_POST['punto_pedido']) ? $_POST['punto_pedido'] : null;

    $id_primario = !empty($_POST['id_contacto_primario']) ? $_POST['id_contacto_primario'] : null;
    $costo_primario = !empty($_POST['costo_primario']) ? $_POST['costo_primario'] : null;

    $id_secundario = !empty($_POST['id_contacto_secundario']) ? $_POST['id_contacto_secundario'] : null;
    $costo_secundario = !empty($_POST['costo_secundario']) ? $_POST['costo_secundario'] : null;

    // Always update date on save for now
    $fecha_cotizacion = date('Y-m-d');

    try {
        if ($id) {
            // Update
            $sql = "UPDATE maestro_materiales SET 
                    nombre = ?, descripcion = ?, unidad_medida = ?, punto_pedido = ?, 
                    id_contacto_primario = ?, costo_primario = ?,
                    id_contacto_secundario = ?, costo_secundario = ?,
                    fecha_ultima_cotizacion = ?
                    WHERE id_material = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $descripcion,
                $unidad_medida,
                $punto_pedido,
                $id_primario,
                $costo_primario,
                $id_secundario,
                $costo_secundario,
                $fecha_cotizacion,
                $id
            ]);

            // Registrar auditoría - Edición
            registrarAccion('EDITAR', 'materiales', "Material editado: $nombre", $id);
        } else {
            // Insert
            $sql = "INSERT INTO maestro_materiales 
                    (nombre, descripcion, unidad_medida, punto_pedido, 
                    id_contacto_primario, costo_primario, 
                    id_contacto_secundario, costo_secundario, 
                    fecha_ultima_cotizacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $descripcion,
                $unidad_medida,
                $punto_pedido,
                $id_primario,
                $costo_primario,
                $id_secundario,
                $costo_secundario,
                $fecha_cotizacion
            ]);

            $newId = $pdo->lastInsertId();

            // Registrar auditoría - Creación
            registrarAccion('CREAR', 'materiales', "Material creado: $nombre", $newId);
        }

        header("Location: index.php?msg=saved");
        exit();

    } catch (PDOException $e) {
        // [!] ARQUITECTURA: No exponer errores SQL al usuario
        error_log("Error en materiales/save.php: " . $e->getMessage());
        header("Location: index.php?msg=error");
        exit();
    }
}
?>