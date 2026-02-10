<?php
/**
 * Sistema de Autenticación y Autorización
 * ERP Recursos Globales
 * 
 * Funciones centrales para:
 * - Iniciar/cerrar sesión
 * - Verificar autenticación
 * - Control de permisos por rol
 * - Registro de auditoría
 */

// Iniciar sesión PHP si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permisos.php';

/**
 * Inicia sesión de usuario validando credenciales
 * 
 * @param string $email Email del usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
/**
 * Inicia sesión de usuario validando credenciales
 * 
 * @param string $email Email del usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function iniciarSesion($email, $password)
{
    global $pdo;

    try {
        // Verificar si la columna id_cuadrilla existe en la tabla usuarios
        $columnExists = false;
        try {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'id_cuadrilla'");
            $columnExists = $checkColumn->rowCount() > 0;
        } catch (PDOException $e) {
            $columnExists = false;
        }

        // Query adaptado según si existe la columna
        // [MOD] 2024-02-06 Cambiado 'rol' a 'tipo_usuario'
        if ($columnExists) {
            $stmt = $pdo->prepare("
                    SELECT u.id_usuario, u.nombre, u.email, u.password_hash, u.tipo_usuario, u.estado, u.id_cuadrilla, c.nombre_cuadrilla 
                    FROM usuarios u
                    LEFT JOIN cuadrillas c ON u.id_cuadrilla = c.id_cuadrilla
                    WHERE u.email = ? AND u.estado = 1
                ");
        } else {
            $stmt = $pdo->prepare("
                    SELECT u.id_usuario, u.nombre, u.email, u.password_hash, u.tipo_usuario, u.estado, NULL as id_cuadrilla, NULL as nombre_cuadrilla 
                    FROM usuarios u
                    WHERE u.email = ? AND u.estado = 1
                ");
        }

        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado o inactivo',
                'user' => null
            ];
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Contraseña incorrecta',
                'user' => null
            ];
        }

        // Crear sesión
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_tipo'] = $usuario['tipo_usuario']; // [MOD] Renamed from usuario_rol
        $_SESSION['usuario_id_cuadrilla'] = $usuario['id_cuadrilla'] ?? null;
        $_SESSION['usuario_nombre_cuadrilla'] = $usuario['nombre_cuadrilla'] ?? null;
        $_SESSION['login_time'] = time();

        // Registrar acción de login (ignorar errores si tabla no existe)
        try {
            registrarAccion('LOGIN', 'auth', 'Inicio de sesión exitoso');
        } catch (PDOException $e) {
            // Tabla de auditoría puede no existir aún
        }

        return [
            'success' => true,
            'message' => 'Sesión iniciada correctamente',
            'user' => $usuario
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'user' => null
        ];
    }
}

/**
 * Cierra la sesión actual
 * 
 * @return void
 */
function cerrarSesion()
{
    // Registrar logout antes de destruir sesión
    if (isset($_SESSION['usuario_id'])) {
        registrarAccion('LOGOUT', 'auth', 'Cierre de sesión');
    }

    // Destruir sesión
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Verifica si hay sesión activa, redirige a login si no
 * 
 * @param bool $redirect Si debe redirigir a login
 * @return bool True si hay sesión activa
 */
function verificarSesion($redirect = true)
{
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        if ($redirect) {
            header('Location: /APP-Prueba/login.php');
            exit;
        }
        return false;
    }
    return true;
}

/**
 * Obtiene los datos del usuario actual en sesión
 * 
 * @return array|null Datos del usuario o null si no hay sesión
 */
function obtenerUsuarioActual()
{
    if (!verificarSesion(false)) {
        return null;
    }

    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? null,
        'email' => $_SESSION['usuario_email'] ?? null,
        'tipo_usuario' => $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? null, // [FIX] Fallback para sesiones antiguas
        'id_cuadrilla' => $_SESSION['usuario_id_cuadrilla'] ?? null,
        'nombre_cuadrilla' => $_SESSION['usuario_nombre_cuadrilla'] ?? null
    ];
}

/**
 * Verifica si el usuario actual tiene permiso para acceder a un módulo
 * 
 * @param string $modulo Nombre del módulo
 * @return bool True si tiene permiso
 */
function tienePermiso($modulo)
{
    global $PERMISOS;

    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }

    $tipo = $usuario['tipo_usuario']; // [MOD] Renamed

    if (!isset($PERMISOS[$tipo])) {
        return false;
    }

    $permisosRol = $PERMISOS[$tipo];

    // Wildcard: acceso total
    if (in_array('*', $permisosRol)) {
        return true;
    }

    return in_array($modulo, $permisosRol);
}

/**
 * Verifica permiso y redirige si no tiene acceso
 * 
 * @param string $modulo Nombre del módulo
 * @return void
 */
function verificarPermiso($modulo)
{
    if (!tienePermiso($modulo)) {
        // Registrar intento de acceso denegado
        registrarAccion('VER', $modulo, 'Acceso denegado - sin permisos');
        header('Location: /APP-Prueba/index.php?error=permisos');
        exit;
    }
}

/**
 * Registra una acción en la tabla de auditoría
 * 
 * @param string $accion Tipo de acción (LOGIN, LOGOUT, CREAR, EDITAR, ELIMINAR, VER)
 * @param string $modulo Módulo afectado
 * @param string $descripcion Descripción de la acción
 * @param int|null $idRegistro ID del registro afectado (opcional)
 * @return bool True si se registró correctamente
 */
function registrarAccion($accion, $modulo, $descripcion = '', $idRegistro = null)
{
    global $pdo;

    $usuarioId = $_SESSION['usuario_id'] ?? null;

    if (!$usuarioId) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
                INSERT INTO auditoria_acciones 
                (id_usuario, accion, modulo, descripcion, id_registro_afectado, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

        $stmt->execute([
            $usuarioId,
            $accion,
            $modulo,
            $descripcion,
            $idRegistro,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255)
        ]);

        return true;

    } catch (PDOException $e) {
        // Log error pero no interrumpir flujo
        error_log("Error registrando auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene las iniciales del nombre para el avatar
 * 
 * @param string $nombre Nombre completo
 * @return string Iniciales (máximo 2 caracteres)
 */
function obtenerIniciales($nombre)
{
    $palabras = explode(' ', trim($nombre));
    $iniciales = '';

    foreach ($palabras as $palabra) {
        if (!empty($palabra)) {
            $iniciales .= strtoupper(mb_substr($palabra, 0, 1));
            if (strlen($iniciales) >= 2)
                break;
        }
    }

    return $iniciales ?: 'U';
}

/**
 * Obtiene el color del badge según el tipo de usuario
 * 
 * @param string $tipo Tipo de usuario
 * @return string Clase CSS para el color
 */
function obtenerColorTipoUsuario($tipo)
{
    $colores = [
        'Gerente' => 'badge-gerente',
        'Coordinador ASSA' => 'badge-info',
        'Administrativo' => 'badge-admin',
        'Administrativo ASSA' => 'badge-success',
        'Inspector ASSA' => 'badge-warning',
        'JefeCuadrilla' => 'badge-jefe'
    ];

    return $colores[$tipo] ?? 'badge-default';
}

?>