<?php
// Configuración de Base de Datos
$host = '127.0.0.1';
$db_name = 'erp_global';
$username = 'root';
$password = ''; // XAMPP Default

// [!] ARCH: Configuración de Base de Datos - Validar conexión local
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);

    // [✓] AUDITORÍA: Configurar el manejo de errores para capturar fallos de SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // [→] EDITAR AQUÍ: Si falla en localhost, verificar que MySQL en XAMPP esté encendido
    die("Error de conexión (AUDIT): " . $e->getMessage());
}
?>