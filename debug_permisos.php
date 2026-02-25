<?php
// debug_permisos.php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Diagnóstico de Permisos</h1>";

if (!isset($_SESSION['usuario_id'])) {
    echo "<p style='color: red;'>No hay sesión iniciada.</p>";
} else {
    echo "<p><strong>ID Usuario:</strong> " . $_SESSION['usuario_id'] . "</p>";
    echo "<p><strong>Nombre:</strong> " . ($_SESSION['usuario_nombre'] ?? 'N/A') . "</p>";
    echo "<p><strong>Rol (usuario_rol):</strong> " . ($_SESSION['usuario_rol'] ?? '<span style="color:red">No definido</span>') . "</p>";
    echo "<p><strong>Tipo (usuario_tipo):</strong> " . ($_SESSION['usuario_tipo'] ?? '<span style="color:red">No definido</span>') . "</p>";

    $rolReal = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? 'Indefinido';
    echo "<hr>";
    echo "<p><strong>Rol Efectivo detectado:</strong> $rolReal</p>";

    $tiene = tienePermiso('compras');
    echo "<p><strong>Tiene permiso 'compras':</strong> " . ($tiene ? '<span style="color:green; font-weight:bold;">SI</span>' : '<span style="color:red; font-weight:bold;">NO</span>') . "</p>";

    echo "<hr>";
    echo "<h3>Configuración de Permisos para '$rolReal':</h3>";
    if (isset($PERMISOS[$rolReal])) {
        echo "<pre style='background: #eee; padding: 10px;'>" . print_r($PERMISOS[$rolReal], true) . "</pre>";
    } else {
        echo "<p style='color: red;'>El rol '$rolReal' NO existe en la matriz de permisos.</p>";
        echo "<p>Roles disponibles:</p>";
        echo "<pre>" . print_r(array_keys($PERMISOS), true) . "</pre>";
    }
}
echo "</div>";
?>