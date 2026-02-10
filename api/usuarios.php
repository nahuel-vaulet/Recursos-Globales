<?php
/**
 * Endpoint API de Usuarios
 * Operaciones CRUD para usuarios
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getUsuario((int) $_GET['id']);
            } else {
                getUsuarios();
            }
            break;
        case 'POST':
            createUsuario();
            break;
        case 'PUT':
            updateUsuario();
            break;
        case 'DELETE':
            deleteUsuario();
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function getUsuarios()
{
    $where = ['u.estado = 1'];
    $params = [];

    if (!empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $where[] = '(u.nombre LIKE :search OR u.email LIKE :search)';
        $params['search'] = $search;
    }

    if (!empty($_GET['tipo_usuario'])) {
        $where[] = 'u.tipo_usuario = :tipo_usuario';
        $params['tipo_usuario'] = $_GET['tipo_usuario'];
    }

    $whereClause = implode(' AND ', $where);

    $sql = "SELECT u.id, u.nombre, u.email, u.tipo_usuario, u.estado, u.created_at,
                   (SELECT COUNT(*) FROM movimientos m WHERE m.usuario_id = u.id) as total_movimientos
            FROM usuarios u
            WHERE {$whereClause}
            ORDER BY u.nombre ASC";

    $usuarios = Database::query($sql, $params);

    jsonResponse([
        'success' => true,
        'data' => $usuarios
    ]);
}

function getUsuario(int $id)
{
    $usuario = Database::queryOne(
        "SELECT id, nombre, email, rol, estado, created_at FROM usuarios WHERE id = :id AND estado = 1",
        ['id' => $id]
    );

    if (!$usuario) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    jsonResponse([
        'success' => true,
        'data' => $usuario
    ]);
}

function createUsuario()
{
    $data = getJsonBody();

    $errors = validateRequired($data, ['nombre', 'email', 'password', 'rol']);

    // Validar formato de email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email no válido';
    }

    // Validar tipo de usuario
    if (!empty($data['tipo_usuario']) && !in_array($data['tipo_usuario'], ['Gerente', 'Administrativo', 'JefeCuadrilla', 'Coordinador ASSA', 'Administrativo ASSA', 'Inspector ASSA'])) {
        $errors[] = 'Tipo de usuario no válido';
    }

    // Validar longitud de contraseña
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }

    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }

    // Verificar email duplicado
    $exists = Database::queryOne(
        "SELECT id FROM usuarios WHERE email = :email",
        ['email' => $data['email']]
    );

    if ($exists) {
        jsonResponse(['error' => 'El email ya está registrado'], 400);
    }

    $sql = "INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario)
            VALUES (:nombre, :email, :password_hash, :tipo_usuario)";

    $params = [
        'nombre' => sanitize($data['nombre']),
        'email' => sanitize($data['email']),
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'tipo_usuario' => $data['tipo_usuario'],
    ];

    Database::execute($sql, $params);
    $id = Database::lastInsertId();

    jsonResponse([
        'success' => true,
        'message' => 'Usuario creado correctamente',
        'id' => $id
    ], 201);
}

function updateUsuario()
{
    $data = getJsonBody();

    if (empty($data['id'])) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    $id = (int) $data['id'];

    $current = Database::queryOne(
        "SELECT * FROM usuarios WHERE id = :id",
        ['id' => $id]
    );

    if (!$current) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    // Verificar email duplicado
    if (!empty($data['email']) && $data['email'] !== $current['email']) {
        $exists = Database::queryOne(
            "SELECT id FROM usuarios WHERE email = :email AND id != :id",
            ['email' => $data['email'], 'id' => $id]
        );
        if ($exists) {
            jsonResponse(['error' => 'El email ya está registrado'], 400);
        }
    }

    // Construir query de actualización
    $updates = [
        'nombre = :nombre',
        'email = :email',
        'tipo_usuario = :tipo_usuario'
    ];

    $params = [
        'id' => $id,
        'nombre' => sanitize($data['nombre'] ?? $current['nombre']),
        'email' => sanitize($data['email'] ?? $current['email']),
        'tipo_usuario' => $data['tipo_usuario'] ?? $current['tipo_usuario'],
    ];

    // Actualizar contraseña si se proporciona
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            jsonResponse(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }
        $updates[] = 'password_hash = :password_hash';
        $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    $updateClause = implode(', ', $updates);
    Database::execute("UPDATE usuarios SET {$updateClause} WHERE id = :id", $params);

    jsonResponse([
        'success' => true,
        'message' => 'Usuario actualizado correctamente'
    ]);
}

function deleteUsuario()
{
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    // No permitir eliminar al último gerente
    $adminCount = Database::queryOne(
        "SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'Gerente' AND estado = 1"
    );

    $user = Database::queryOne(
        "SELECT * FROM usuarios WHERE id = :id",
        ['id' => $id]
    );

    if ($user && $user['tipo_usuario'] === 'Gerente' && $adminCount['total'] <= 1) {
        jsonResponse(['error' => 'No se puede eliminar el último Gerente'], 400);
    }

    // Borrado lógico (Soft delete)
    Database::execute(
        "UPDATE usuarios SET estado = 0 WHERE id = :id",
        ['id' => $id]
    );

    jsonResponse([
        'success' => true,
        'message' => 'Usuario eliminado correctamente'
    ]);
}
