<?php
/**
 * Setup Script - HARD RESET VERSION
 * BORRA Y RE-CREA TODO DESDE CERO
 */

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'stock_management';

$messages = [];

function logMsg($msg, $type = 'info')
{
    global $messages;
    $messages[] = ['text' => $msg, 'type' => $type];
}

try {
    // 1. Connect
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. DROP DATABASE (Hard Reset)
    logMsg("🗑️ Eliminando base de datos antigua...", "warning");
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");

    // 3. Create Fresh DB
    logMsg("✨ Creando base de datos limpia...", "info");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // 4. Run SQL Schema
    logMsg("📝 Creando tablas y usuarios...", "info");
    $sqlFile = __DIR__ . '/sql/schema.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("ERROR CRÍTICO: No encuentro el archivo sql/schema.sql en " . __DIR__);
    }

    $sql = file_get_contents($sqlFile);

    // Fix: Remove any potential garbage characters
    $sql = preg_replace('/^[\xEF\xBB\xBF]/', '', $sql);

    // Execute
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    logMsg("✅ TODO INSTALADO DESDE CERO.", "success");
    logMsg("Usuario creado: admin@stock.local / admin123", "success");

} catch (Exception $e) {
    logMsg("❌ ERROR: " . $e->getMessage(), "error");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinstalación Total</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            margin-top: 0;
            color: #dc2626;
            text-align: center;
        }

        .item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .success {
            color: #16a34a;
            font-weight: bold;
        }

        .error {
            color: #dc2626;
            font-weight: bold;
        }

        .warning {
            color: #ca8a04;
        }

        .btn {
            display: block;
            background: #2563eb;
            color: white;
            text-align: center;
            padding: 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 20px;
        }

        .btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>⚠️ Reinstalación Total</h1>
        <?php foreach ($messages as $m): ?>
            <div class="item <?= $m['type'] ?>">
                <?= $m['type'] === 'success' ? '✅' : ($m['type'] === 'error' ? '❌' : 'ℹ️') ?>
                <?= htmlspecialchars($m['text']) ?>
            </div>
        <?php endforeach; ?>

        <a href="index.php" class="btn">🚀 IR AL SISTEMA</a>
    </div>
</body>

</html>