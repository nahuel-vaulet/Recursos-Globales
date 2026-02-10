<?php
/**
 * Logout - Cierre de sesión
 * ERP Recursos Globales
 */

require_once 'includes/auth.php';

// Cerrar sesión
cerrarSesion();

// Redirigir al login
header('Location: /APP-Prueba/login.php');
exit;
?>