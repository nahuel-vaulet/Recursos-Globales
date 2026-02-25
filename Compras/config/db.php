<?php
$host = 'localhost';
$db   = 'modulo_compras';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Attempt to connect without db to create it if it doesn't exist
    try {
        $dsn_no_db = "mysql:host=$host;charset=$charset";
        $pdo_temp = new PDO($dsn_no_db, $user, $pass, $options);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db`");
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e2) {
        throw new \PDOException($e2->getMessage(), (int)$e2->getCode());
    }
}
?>
