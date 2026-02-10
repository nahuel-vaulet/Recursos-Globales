<?php
/**
 * Header Component con Autenticación
 * ERP Recursos Globales
 * 
 * Verifica sesión y genera menú filtrado por permisos
 */

require_once __DIR__ . '/auth.php';

// Verificar sesión (redirige a login si no hay)
verificarSesion();

// Obtener usuario actual
$usuarioActual = obtenerUsuarioActual();
$menuFiltrado = generarMenuFiltrado($usuarioActual['tipo_usuario']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- [!] PWA: Enforce HTTPS (Disabled for HTTP+Flag strategy) -->
    <!-- 
    <script>
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            location.replace('https:' + location.href.substring(location.protocol.length));
        }
    </script>
    -->

    <!-- [!] PWA: Meta tags para instalabilidad Android -->
    <meta name="theme-color" content="#16213e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/APP-Prueba/manifest.json">
    <link rel="apple-touch-icon" href="/APP-Prueba/icons/icon-192.png">

    <title>Recursos Globales - ERP</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS (Absolute Path with Cache Buster) -->
    <link rel="stylesheet" href="/APP-Prueba/assets/css/style.css?v=2024020101">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos adicionales para roles */
        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .user-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--color-primary-dark);
        }

        .user-info {
            text-align: left;
            line-height: 1.3;
        }

        .user-name {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .badge-gerente {
            background: #ffd700;
            color: #333;
        }

        .badge-admin {
            background: #4CAF50;
            color: white;
        }

        .badge-jefe {
            background: #2196F3;
            color: white;
        }

        .user-role-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 500;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--bg-card);
            border: 1px solid rgba(100, 181, 246, 0.15);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1000;
        }

        .user-dropdown:hover .dropdown-menu,
        .user-dropdown:focus-within .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        [data-theme="light"] .dropdown-header {
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-header .avatar-lg {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            font-size: 1.2rem;
        }

        .dropdown-header .name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .dropdown-header .email {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--text-primary) !important;
            text-decoration: none;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-primary) !important;
        }

        [data-theme="light"] .dropdown-item:hover {
            background: #f5f5f5;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 5px 0;
        }

        [data-theme="light"] .dropdown-divider {
            background: #f0f0f0;
        }

        .dropdown-item.logout {
            color: #dc3545 !important;
        }

        .dropdown-item.logout:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #ef4444 !important;
        }

        /* Cuadrilla badge para Jefe */
        .cuadrilla-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-top: 5px;
            display: inline-block;
        }

        /* ===  ESTILOS MODO CLARO === */
        [data-theme="light"] .user-btn {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
            color: #1a1a2e;
        }

        [data-theme="light"] .user-btn:hover {
            background: rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.15);
        }

        [data-theme="light"] .user-avatar {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #1a1a2e;
        }

        [data-theme="light"] .nav-links a {
            color: #4a5568 !important;
        }

        [data-theme="light"] .nav-links a:hover {
            color: #2563eb !important;
        }

        [data-theme="light"] .navbar-brand {
            color: #1a1a2e !important;
        }

        [data-theme="light"] .navbar-brand span {
            color: #1a1a2e;
        }

        /* ========================================
           TOGGLE MODO OSCURO - NEUMÓRFICO
           ======================================== */
        .theme-switch {
            position: relative;
            display: inline-block;
            width: 68px;
            height: 34px;
            cursor: pointer;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-slider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(145deg, var(--bg-secondary), var(--bg-dark));
            border-radius: 34px;
            box-shadow: inset 3px 3px 8px rgba(0, 0, 0, 0.4),
                inset -3px -3px 8px rgba(255, 255, 255, 0.05),
                0 2px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8px;
        }

        .switch-slider .sun-icon {
            color: #ffd93d;
            font-size: 0.9rem;
            opacity: 0.4;
            transition: all 0.3s ease;
        }

        .switch-slider .moon-icon {
            color: #64b5f6;
            font-size: 0.85rem;
            opacity: 1;
            transition: all 0.3s ease;
        }

        .switch-slider::before {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            left: 4px;
            bottom: 4px;
            background: linear-gradient(145deg, #274060, #1b263b);
            border-radius: 50%;
            box-shadow: -2px -2px 6px rgba(255, 255, 255, 0.08),
                2px 2px 6px rgba(0, 0, 0, 0.4),
                0 0 12px rgba(100, 181, 246, 0.3);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 2;
        }

        /* Estado claro (unchecked) */
        .theme-switch input:not(:checked)+.switch-slider {
            background: linear-gradient(145deg, #e8e8e8, #d0d0d0);
        }

        .theme-switch input:not(:checked)+.switch-slider::before {
            transform: translateX(34px);
            background: linear-gradient(145deg, #fff, #e0e0e0);
            box-shadow: -2px -2px 6px rgba(255, 255, 255, 1),
                2px 2px 6px rgba(0, 0, 0, 0.15),
                0 0 12px rgba(255, 215, 61, 0.4);
        }

        .theme-switch input:not(:checked)+.switch-slider .sun-icon {
            opacity: 1;
            color: #ff9500;
        }

        .theme-switch input:not(:checked)+.switch-slider .moon-icon {
            opacity: 0.3;
            color: #666;
        }
    </style>
</head>

<body>

    <header class="navbar">
        <a href="/APP-Prueba/index.php" class="navbar-brand">
            <img src="/APP-Prueba/assets/img/RG_Logo.png?v=2" alt="RG Logo">
            <span>Recursos Globales</span>
        </a>

        <ul class="nav-links">
            <?php if ($usuarioActual['tipo_usuario'] !== 'JefeCuadrilla'): ?>
                <li class="nav-item"><a href="/APP-Prueba/index.php"><i class="fas fa-home"></i> Inicio</a></li>
            <?php endif; ?>

            <!-- Mega Menu Item - Filtrado por permisos -->
            <?php if (!empty($menuFiltrado)): ?>
                <li class="nav-item mega-menu-trigger">
                    <a href="#">Módulos <i class="fas fa-chevron-down"></i></a>
                    <div class="mega-menu">
                        <div class="mega-menu-grid">
                            <?php foreach ($menuFiltrado as $seccion => $datos): ?>
                                <div class="mega-column">
                                    <h4><i class="<?php echo $datos['icono']; ?>"></i>
                                        <?php echo $datos['titulo']; ?>
                                    </h4>
                                    <ul>
                                        <?php foreach ($datos['items'] as $item): ?>
                                            <li><a href="<?php echo $item['url']; ?>">
                                                    <?php echo $item['nombre']; ?>
                                                </a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
            <?php endif; ?>

            <li class="nav-item"><a href="#"><i class="fas fa-bell"></i></a></li>

            <!-- Toggle Modo Oscuro/Claro -->
            <li class="nav-item">
                <label class="theme-switch" title="Cambiar tema">
                    <input type="checkbox" id="themeToggle" checked>
                    <span class="switch-slider">
                        <i class="fas fa-sun sun-icon"></i>
                        <i class="fas fa-moon moon-icon"></i>
                    </span>
                </label>
            </li>

            <!-- Botón Logout directo para móvil -->
            <li class="nav-item logout-mobile">
                <a href="/APP-Prueba/logout.php" title="Cerrar Sesión" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>

            <!-- User Dropdown -->
            <li class="nav-item user-dropdown">
                <button class="user-btn">
                    <div class="user-avatar">
                        <?php echo obtenerIniciales($usuarioActual['nombre']); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name">
                            <?php echo htmlspecialchars($usuarioActual['nombre']); ?>
                        </div>
                        <span
                            class="user-role-badge <?php echo obtenerColorTipoUsuario($usuarioActual['tipo_usuario']); ?>">
                            <?php
                            $rolDisplay = $usuarioActual['tipo_usuario'];
                            if ($rolDisplay === 'JefeCuadrilla')
                                $rolDisplay = 'Jefe de Cuadrilla';
                            echo $rolDisplay;
                            ?>
                        </span>
                    </div>
                    <i class="fas fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
                </button>

                <div class="dropdown-menu">
                    <div class="dropdown-header">
                        <div class="user-avatar avatar-lg">
                            <?php echo obtenerIniciales($usuarioActual['nombre']); ?>
                        </div>
                        <div class="name">
                            <?php echo htmlspecialchars($usuarioActual['nombre']); ?>
                        </div>
                        <div class="email">
                            <?php echo htmlspecialchars($usuarioActual['email']); ?>
                        </div>
                        <?php if ($usuarioActual['tipo_usuario'] === 'JefeCuadrilla' && $usuarioActual['nombre_cuadrilla']): ?>
                            <span class="cuadrilla-badge">
                                <i class="fas fa-hard-hat"></i>
                                <?php echo htmlspecialchars($usuarioActual['nombre_cuadrilla']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>

                    <div class="dropdown-divider"></div>

                    <a href="/APP-Prueba/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </li>
        </ul>
    </header>

    <main class="container">

        <!-- Toast Container (ASSA Style) -->
        <div id="toastContainer" class="toast-container"></div>

        <script>
            /**
             * Sistema de Notificaciones Minimalista 
             */
            function showToast(message, type = 'success', duration = 3000) {
                const container = document.getElementById('toastContainer');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `toast ${type}`;

                const icon = type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                        type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

                toast.innerHTML = `
                <i class="fas ${icon}"></i>
                <span>${message}</span>
                <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
            `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.animation = 'toastSlideIn 0.3s reverse forwards';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            // Auto-show toasts from URL msg parameter
            window.addEventListener('load', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const msg = urlParams.get('msg');
                if (msg === 'saved') showToast('✓ Usuario guardado correctamente', 'success');
                if (msg === 'error') showToast('⚠ Error: No se pudieron guardar los cambios', 'error');
                if (msg === 'forbidden') showToast('⚠ Acceso denegado', 'error');
                if (msg === 'duplicate') showToast('⚠ El email ya está registrado', 'warning');
                if (msg === 'deleted') showToast('✓ Usuario eliminado', 'success');
            });
        </script>