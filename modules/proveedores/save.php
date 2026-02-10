<?php
/**
 * Módulo: Proveedores
 * Endpoint para guardar (crear/actualizar)
 * 
 * [!] ARQUITECTURA: Maneja proveedor + contacto en una transacción
 * [→] EDITAR AQUÍ: Rutas de inclusión si cambia la estructura
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDITORÍA CRUD: Verificación de sesión obligatoria
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Datos del Proveedor
$id_prov = $_POST['id_proveedor'] ?? null;
$razon = trim($_POST['razon_social'] ?? '');
$cuit = trim($_POST['cuit'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

// Datos del Contacto
$id_cont = $_POST['id_contacto'] ?? null;
$nom = trim($_POST['nombre_vendedor'] ?? '');
$tel = trim($_POST['telefono_contacto'] ?? '');
$email = trim($_POST['email_vendedor'] ?? '');

// Logística
$pago = $_POST['condicion_pago'] ?? '';
$entrega = $_POST['modalidad_entrega'] ?? '';
$dias = $_POST['dias_atencion'] ?? '';
$horas = $_POST['horarios_atencion'] ?? '';
$extra = trim($_POST['notas_extra'] ?? '');

// [!] ARQUITECTURA: Compilar observaciones
$obs_parts = [];
if ($pago)
    $obs_parts[] = "Pago: $pago";
if ($entrega)
    $obs_parts[] = "Entrega: $entrega";
if ($dias || $horas)
    $obs_parts[] = "Horario: $dias $horas";
if ($extra)
    $obs_parts[] = "Notas: $extra";
$observaciones_final = implode(" | ", $obs_parts);

// Validación
if (empty($razon)) {
    $_SESSION['error'] = 'La razón social es obligatoria.';
    header('Location: form.php' . ($id_prov ? "?id=$id_prov" : ''));
    exit;
}

try {
    $pdo->beginTransaction();

    if ($id_prov) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE proveedores SET razon_social = ?, cuit = ?, direccion = ? WHERE id_proveedor = ?");
        $stmt->execute([$razon, $cuit, $direccion, $id_prov]);

        if ($id_cont) {
            $stmt = $pdo->prepare("UPDATE proveedores_contactos SET nombre_vendedor = ?, telefono_contacto = ?, email_vendedor = ?, observaciones = ? WHERE id_contacto = ?");
            $stmt->execute([$nom, $tel, $email, $observaciones_final, $id_cont]);
        }

        // [✓] AUDITORÍA CRUD: Registrar edición
        registrarAccion('EDITAR', 'proveedores', "Proveedor editado: $razon", $id_prov);

    } else {
        // INSERT
        $stmt = $pdo->prepare("INSERT INTO proveedores (razon_social, cuit, direccion) VALUES (?, ?, ?)");
        $stmt->execute([$razon, $cuit, $direccion]);
        $id_prov = $pdo->lastInsertId();

        if (!empty($nom) || !empty($email)) {
            $stmt = $pdo->prepare("INSERT INTO proveedores_contactos (id_proveedor, nombre_vendedor, telefono_contacto, email_vendedor, observaciones) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_prov, $nom, $tel, $email, $observaciones_final]);
        }

        // [✓] AUDITORÍA CRUD: Registrar creación
        registrarAccion('CREAR', 'proveedores', "Proveedor creado: $razon", $id_prov);
    }

    $pdo->commit();
    header("Location: index.php?msg=saved");

} catch (PDOException $e) {
    $pdo->rollBack();
    // [!] ARQUITECTURA: No exponer errores SQL
    error_log("Error en proveedores/save.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error al guardar el proveedor.';
    header('Location: form.php' . ($id_prov ? "?id=$id_prov" : ''));
}

exit;
?>