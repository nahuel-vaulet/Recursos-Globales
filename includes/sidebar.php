<?php
/**
 * Sidebar Navigation Component
 * Reusable sidebar include
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <i class="fas fa-warehouse"></i>
        </div>
        <span class="sidebar-logo-text">StockPro</span>
    </div>

    <nav class="sidebar-nav">
        <?php
        $rolActual = $_SESSION['usuario_rol'] ?? 'Operador';
        $menuFiltrado = generarMenuFiltrado($rolActual);
        ?>

        <?php foreach ($menuFiltrado as $seccion => $datos): ?>
            <div class="sidebar-section-title"><?= htmlspecialchars($datos['titulo']) ?></div>
            <ul class="sidebar-menu">
                <?php foreach ($datos['items'] as $item): ?>
                    <?php
                    $isActive = (basename($_SERVER['PHP_SELF']) === basename($item['url'])) ? 'active' : '';
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= htmlspecialchars($item['url']) ?>" class="sidebar-link <?= $isActive ?>">
                            <i class="<?= htmlspecialchars($item['icono'] ?? 'fas fa-link') ?>"></i>
                            <span><?= htmlspecialchars($item['nombre']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <span><?= obtenerIniciales($_SESSION['usuario_nombre'] ?? 'U') ?></span>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars($_SESSION['usuario_rol'] ?? 'Operador') ?></div>
            </div>
        </div>
    </div>
</aside>