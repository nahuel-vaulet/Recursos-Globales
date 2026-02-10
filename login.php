<?php
/**
 * Pantalla de Login
 * ERP Recursos Globales
 * 
 * Auto-genera usuarios:
 * - Gerente (clave: 999999)
 * - Administrativo (clave: 666666)  
 * - Jefe [Nombre Cuadrilla] por cada cuadrilla (clave: 123456)
 */

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// ============================================
// AUTO-SETUP: Sincronizar usuarios con cuadrillas
// ============================================
function sincronizarUsuarios($pdo) {
    try {
        // Primero asegurar que la tabla usuarios tenga los campos necesarios
        // [!] ARCH: Actualizar ENUM de tipo_usuario para incluir perfiles ASSA
        try {
            // [MOD] 2024-02-06 Changed 'rol' to 'tipo_usuario'
            $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN tipo_usuario ENUM('Gerente', 'Administrativo', 'JefeCuadrilla', 'Coordinador ASSA', 'Administrativo ASSA', 'Inspector ASSA') NOT NULL DEFAULT 'Administrativo'");
        } catch (PDOException $e) { 
            // Si falla por registros existentes incompatibles, loguear
            error_log("Error actualizando ENUM tipo_usuario: " . $e->getMessage());
        }
        
        // Verificar si existe columna id_cuadrilla
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'id_cuadrilla'");
            if ($checkCol->rowCount() == 0) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN id_cuadrilla INT DEFAULT NULL");
                $pdo->exec("ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_cuadrilla FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL");
            }
        } catch (PDOException $e) { /* Ya existe */ }

        // Hash de contraseñas
        $hashGerente = password_hash('999999', PASSWORD_BCRYPT);
        $hashAdmin = password_hash('666666', PASSWORD_BCRYPT);
        $hashCuadrilla = password_hash('123456', PASSWORD_BCRYPT);

        // 1. Crear/actualizar Gerente
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute(['gerente@erp.com']);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario, estado) VALUES (?, ?, ?, ?, 1)")
                ->execute(['Gerente', 'gerente@erp.com', $hashGerente, 'Gerente']);
        }

        // 2. Crear/actualizar Administrativo
        $stmt->execute(['admin@erp.com']);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario, estado) VALUES (?, ?, ?, ?, 1)")
                ->execute(['Administrativo', 'admin@erp.com', $hashAdmin, 'Administrativo']);
        }

        // 3. Sincronizar usuarios de cuadrillas
        $cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo != 'Baja'")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cuadrillas as $c) {
            $email = 'jefe.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $c['nombre_cuadrilla'])) . '@erp.com';
            $nombre = 'Jefe ' . $c['nombre_cuadrilla'];
            
            // Verificar si existe
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_cuadrilla = ?");
            $stmt->execute([$c['id_cuadrilla']]);
            $existe = $stmt->fetch();
            
            if (!$existe) {
                // Crear usuario para esta cuadrilla
                $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario, id_cuadrilla, estado) VALUES (?, ?, ?, 'JefeCuadrilla', ?, 1)")
                    ->execute([$nombre, $email, $hashCuadrilla, $c['id_cuadrilla']]);
            } else {
                // Actualizar nombre por si cambió la cuadrilla
                $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id_cuadrilla = ?")
                    ->execute([$nombre, $c['id_cuadrilla']]);
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error sincronizando usuarios: " . $e->getMessage());
        return false;
    }
}

// Ejecutar sincronización
sincronizarUsuarios($pdo);

// Obtener usuarios disponibles para el dropdown
$usuariosDisponibles = [];
try {
    $usuariosDisponibles = $pdo->query("
        SELECT u.id_usuario, u.nombre, u.email, u.tipo_usuario, c.nombre_cuadrilla
        FROM usuarios u
        LEFT JOIN cuadrillas c ON u.id_cuadrilla = c.id_cuadrilla
        WHERE u.estado = 1
        ORDER BY 
            CASE u.tipo_usuario 
                WHEN 'Gerente' THEN 1 
                WHEN 'Coordinador ASSA' THEN 2
                WHEN 'Administrativo' THEN 3
                WHEN 'Administrativo ASSA' THEN 4
                WHEN 'Inspector ASSA' THEN 5
                ELSE 6 
            END,
            u.nombre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuariosDisponibles = [];
}

require_once 'includes/auth.php';

// Si ya tiene sesión, redirigir según rol
if (verificarSesion(false)) {
    $usuario = obtenerUsuarioActual();
    if ($usuario['tipo_usuario'] === 'JefeCuadrilla') {
        header('Location: /APP-Prueba/modules/cuadrillas/index.php');
    } else {
        header('Location: /APP-Prueba/index.php');
    }
    exit;
}

$error = '';
$email = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $resultado = iniciarSesion($email, $password);

        if ($resultado['success']) {
            $tipo = $_SESSION['usuario_tipo']; // [MOD] Renamed
            if ($tipo === 'JefeCuadrilla') {
                header('Location: /APP-Prueba/modules/cuadrillas/index.php');
            } else {
                header('Location: /APP-Prueba/index.php');
            }
            exit;
        } else {
            $error = $resultado['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Recursos Globales ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-primary-dark: #004A7F;
            --color-primary: #0073A8;
            --color-primary-light: #009FD7;
            --color-neutral-dark: #333333;
            --color-neutral: #666666;
            --color-neutral-light: #AAAAAA;
            --color-success: #28A745;
            --color-danger: #DC3545;
            --color-background: #F5F5F5;
            --color-surface: #FFFFFF;
            --border-radius-md: 8px;
            --border-radius-lg: 16px;
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
            --font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 50%, var(--color-primary-light) 100%);
            padding: 20px;
        }

        .login-container { width: 100%; max-width: 480px; }

        .login-card {
            background: var(--color-surface);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
            padding: 30px 15px;
            text-align: center;
            color: white;
        }

        .login-logo {
            width: 100%;
            max-width: 320px;
            height: auto;
            min-height: 140px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            padding: 15px;
            overflow: hidden;
        }

        .login-logo img { 
            width: 100%; 
            height: auto; 
            object-fit: contain; 
        }
        .login-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; }
        .login-subtitle { font-size: 0.9rem; opacity: 0.9; }
        .login-body { padding: 25px 20px; }
        .form-group { margin-bottom: 22px; }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-neutral-dark);
            margin-bottom: 8px;
        }

        .form-input-wrapper { position: relative; }

        .form-input-wrapper i.input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-neutral-light);
            transition: color 0.2s;
            pointer-events: none;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius-md);
            font-size: 1rem;
            font-family: var(--font-family);
            transition: all 0.2s;
            background: #fafafa;
            appearance: none;
        }

        .form-select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--color-primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 115, 168, 0.1);
        }

        .form-input-wrapper:focus-within i.input-icon { color: var(--color-primary); }
        .form-input::placeholder { color: var(--color-neutral-light); }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            font-family: var(--font-family);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 74, 127, 0.4); }
        .btn-login:active { transform: translateY(0); }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--color-danger);
            padding: 12px 15px;
            border-radius: var(--border-radius-md);
            margin-bottom: 22px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .login-footer {
            text-align: center;
            padding: 20px 30px 25px;
            border-top: 1px solid #f0f0f0;
        }

        .login-footer p { font-size: 0.8rem; color: var(--color-neutral); }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-neutral-light);
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: var(--color-primary); }
        .user-selector-hint { font-size: 0.75rem; color: var(--color-neutral); margin-top: 6px; font-style: italic; }
        
        optgroup { font-weight: bold; color: var(--color-primary-dark); }
        option { font-weight: normal; color: var(--color-neutral-dark); padding: 8px; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="/APP-Prueba/assets/img/RG_Logo.png" alt="RG Logo">
                </div>
                <h1 class="login-title">Recursos Globales</h1>
                <p class="login-subtitle">Sistema ERP - Iniciar Sesión</p>
            </div>

            <form class="login-body" method="POST" action="">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="email">Usuario</label>
                    <div class="form-input-wrapper">
                        <select id="email" name="email" class="form-select" required>
                            <option value="" disabled <?php echo empty($email) ? 'selected' : ''; ?>>Seleccione un usuario...</option>
                            
                            <?php 
                            $lastTipo = '';
                            foreach ($usuariosDisponibles as $u): 
                                // Agrupar por tipo
                                if ($u['tipo_usuario'] !== $lastTipo):
                                    if ($lastTipo !== '') echo '</optgroup>';
                                    $grupoLabel = match($u['tipo_usuario']) {
                                        'Gerente' => '👔 Gerencia',
                                        'Coordinador ASSA' => '📡 Coordinación ASSA',
                                        'Administrativo' => '📋 Administración General',
                                        'Administrativo ASSA' => '💧 Administración ASSA',
                                        'Inspector ASSA' => '🔍 Inspección ASSA',
                                        'JefeCuadrilla' => '🔧 Jefes de Cuadrilla',
                                        default => 'Otros'
                                    };
                                    echo "<optgroup label=\"$grupoLabel\">";
                                    $lastTipo = $u['tipo_usuario'];
                                endif;
                            ?>
                                <option value="<?php echo htmlspecialchars($u['email']); ?>" 
                                        <?php echo $email === $u['email'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($lastTipo !== '') echo '</optgroup>'; ?>
                        </select>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    <p class="user-selector-hint">Cada tipo de usuario tiene permisos específicos en el sistema</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="form-input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Ingrese su contraseña"
                            required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Ingresar al Sistema
                </button>
                <br>
            <div style="text-align: center; margin-top: 15px;">
                <a href="cert/server.crt" download style="color: #666; text-decoration: none; font-size: 0.8em; border: 1px solid #ccc; padding: 5px 10px; border-radius: 4px;">
                    ⬇️ Descargar Certificado (Android)
                </a>
            </div>
        </form>

            <div class="login-footer">
                <p>© 2024 Recursos Globales Business Company</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        document.getElementById('email').focus();
    </script>
</body>
</html>