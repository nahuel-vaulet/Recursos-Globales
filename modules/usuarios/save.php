<?php
/**
 * Módulo: Usuarios del Sistema
 * Endpoint para guardar (crear/actualizar)
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión y permisos
verificarSesion();
if (!tienePermiso('usuarios')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Recoger datos
$id = $_POST['id_usuario'] ?? null;
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$tipo_usuario = $_POST['tipo_usuario']; // [MOD] Renamed from rol
$id_cuadrilla = $_POST['id_cuadrilla'] ?: null; // Convert empty string to null
$estado = $_POST['estado'];

// Validaciones básicas
if (empty($nombre) || empty($email) || empty($tipo_usuario)) {
    die("Error: Datos incompletos");
}

try {
    // Validar email único
    $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
    if ($id)
        $sql .= " AND id_usuario != ?";

    $stmt = $pdo->prepare($sql);
    $params = [$email];
    if ($id)
        $params[] = $id;
    $stmt->execute($params);

    if ($stmt->fetch()) {
        die("Error: El email ya está registrado");
    }

    if ($id) {
        // ACTUALIZAR
        $sql = "UPDATE usuarios SET 
                nombre = ?, 
                email = ?, 
                tipo_usuario = ?, 
                id_cuadrilla = ?, 
                estado = ?";

        $params = [$nombre, $email, $tipo_usuario, $id_cuadrilla, $estado];

        // Si hay password nuevo, actualizarlo
        if (!empty($password)) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id_usuario = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Registrar auditoría
        registrarAccion('EDITAR', 'usuarios', "Actualizó usuario $nombre ($tipo_usuario)", $id);

    } else {
        // CREAR NUEVO
        if (empty($password)) {
            die("Error: Contraseña requerida para nuevos usuarios");
        }

        $sql = "INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario, id_cuadrilla, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $tipo_usuario,
            $id_cuadrilla,
            $estado
        ]);

        $id = $pdo->lastInsertId();

        // Registrar auditoría
        registrarAccion('CREAR', 'usuarios', "Creó usuario $nombre ($tipo_usuario)", $id);
    }

    header("Location: index.php?msg=saved");
    exit();

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}