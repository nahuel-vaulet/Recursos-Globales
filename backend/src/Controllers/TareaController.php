<?php
/**
 * [!] ARCH: TareaController — Gestión de tareas
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class TareaController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[] = "t.estado = :estado";
            $params['estado'] = $_GET['estado'];
        }

        if (!empty($_GET['responsable_id'])) {
            $where[] = "t.id_responsable = :resp";
            $params['resp'] = (int) $_GET['responsable_id'];
        }

        if (!empty($_GET['search'])) {
            $where[] = "(t.titulo LIKE :search OR t.descripcion LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT t.*, u.nombre as responsable_nombre
            FROM tareas t
            LEFT JOIN usuarios u ON t.id_responsable = u.id_usuario
            WHERE {$whereClause}
            ORDER BY t.fecha_vencimiento ASC, t.prioridad ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT t.*, u.nombre as responsable_nombre FROM tareas t LEFT JOIN usuarios u ON t.id_responsable = u.id_usuario WHERE t.id_tarea = ?");
        $stmt->execute([$id]);
        $t = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$t) {
            Response::json(['error' => 'ERR-TASK-404', 'message' => 'Tarea no encontrada'], 404);
            return;
        }
        Response::json(['data' => $t]);
    }

    public static function store(): void
    {
        $auth = AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['titulo'])) {
            Response::json(['error' => 'ERR-TASK-FIELD', 'message' => 'titulo requerido'], 400);
            return;
        }

        $now = Database::isPostgres() ? 'CURRENT_TIMESTAMP' : 'NOW()';
        $stmt = $pdo->prepare("
            INSERT INTO tareas (titulo, descripcion, id_responsable, prioridad, fecha_vencimiento, estado, created_at)
            VALUES (?, ?, ?, ?, ?, 'pendiente', {$now})
        ");
        $stmt->execute([
            $body['titulo'],
            $body['descripcion'] ?? null,
            !empty($body['id_responsable']) ? (int) $body['id_responsable'] : ($auth['id'] ?? 1),
            (int) ($body['prioridad'] ?? 3),
            $body['fecha_vencimiento'] ?? null,
        ]);

        Response::json(['message' => 'Tarea creada', 'id' => $pdo->lastInsertId()], 201);
    }

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id_tarea = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$current) {
            Response::json(['error' => 'ERR-TASK-404', 'message' => 'No encontrada'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE tareas SET titulo=?, descripcion=?, id_responsable=?, prioridad=?, fecha_vencimiento=?, estado=?
            WHERE id_tarea=?
        ")->execute([
                    $body['titulo'] ?? $current['titulo'],
                    $body['descripcion'] ?? $current['descripcion'],
                    isset($body['id_responsable']) ? (int) $body['id_responsable'] : $current['id_responsable'],
                    $body['prioridad'] ?? $current['prioridad'],
                    $body['fecha_vencimiento'] ?? $current['fecha_vencimiento'],
                    $body['estado'] ?? $current['estado'],
                    $id,
                ]);

        Response::json(['message' => 'Tarea actualizada']);
    }

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        Database::getConnection()->prepare("DELETE FROM tareas WHERE id_tarea = ?")->execute([$id]);
        Response::json(['message' => 'Tarea eliminada']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
