<?php
require_once '../../config/database.php';
session_start();

$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? 'Completada'; // Default legacy fallback
$userId = $_SESSION['usuario_id'] ?? 1;

if ($id) {
    if ($status === 'Completada') {
        $stmt = $pdo->prepare("UPDATE tareas_instancia SET estado = ?, fecha_completada = NOW(), id_responsable = ? WHERE id_tarea = ?");
        $stmt->execute([$status, $userId, $id]);
    } else {
        // Para Pendiente o En Curso, limpiamos fecha_completada si estaba completa
        $stmt = $pdo->prepare("UPDATE tareas_instancia SET estado = ?, fecha_completada = NULL, id_responsable = ? WHERE id_tarea = ?");
        $stmt->execute([$status, $userId, $id]);
    }
}

$redirect = $_GET['redirect'] ?? 'module';

if ($redirect === 'dashboard') {
    header("Location: ../../index.php");
} else {
    header("Location: index.php?view=" . ($_GET['view'] ?? 'list'));
}
exit;
