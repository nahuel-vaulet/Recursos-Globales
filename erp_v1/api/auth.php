<?php
require_once 'config.php';

$body = getRequestBody();
$action = $body['action'] ?? getParam('action');

switch ($action) {
    case 'login':
        handleLogin($body);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleGetCurrentUser();
        break;
    default:
        jsonError('Accion no valida', 400);
}

function handleLogin($data)
{
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password))
        jsonError('Email y contrasena son requeridos', 400);

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonError('Credenciales incorrectas', 401);
    }

    $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?")->execute([$user['id']]);

    $token = generateJWT([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'rol' => $user['rol']
    ]);

    unset($user['password_hash']);

    jsonResponse([
        'success' => true,
        'message' => 'Login exitoso',
        'token' => $token,
        'user' => $user
    ]);
}

function handleLogout()
{
    $payload = requireAuth();
    jsonResponse(['success' => true, 'message' => 'Sesion cerrada']);
}

function handleGetCurrentUser()
{
    $payload = requireAuth();
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nombre, email, rol, estado, ultimo_acceso FROM usuarios WHERE id = ?");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();
    if (!$user)
        jsonError('Usuario no encontrado', 404);
    jsonResponse(['success' => true, 'user' => $user]);
}
