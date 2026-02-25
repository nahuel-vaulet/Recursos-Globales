<?php
/**
 * [!] ARCH: Database Configuration — Dual Mode
 * [✓] AUDIT: Detects DATABASE_URL for Supabase PostgreSQL, falls back to XAMPP MySQL
 * [→] EDITAR: Set DATABASE_URL env var in Render for production
 */

$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // ─── PRODUCCIÓN (Render → Supabase PostgreSQL) ──────
    $dbopts = parse_url($database_url);
    $host = $dbopts['host'];
    $port = $dbopts['port'];
    $user = $dbopts['user'];
    $pass = $dbopts['pass'];
    $db_name = ltrim($dbopts['path'], '/');

    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name";
} else {
    // ─── LOCAL (XAMPP → MySQL) ──────────────────────────
    $host = '127.0.0.1';
    $db_name = 'erp_global';
    $user = 'root';
    $pass = '';

    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión (AUDIT): " . $e->getMessage());
}
?>