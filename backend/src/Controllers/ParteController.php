<?php
/**
 * [!] ARCH: ParteController — Partes diarios de trabajo
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class ParteController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['cuadrilla_id'])) {
            $where[] = "p.id_cuadrilla = :crew";
            $params['crew'] = (int) $_GET['cuadrilla_id'];
        }

        if (!empty($_GET['fecha'])) {
            $where[] = "p.fecha = :fecha";
            $params['fecha'] = $_GET['fecha'];
        }

        if (!empty($_GET['fecha_desde'])) {
            $where[] = "p.fecha >= :f_desde";
            $params['f_desde'] = $_GET['fecha_desde'];
        }

        $whereClause = implode(' AND ', $where);
        $limit = min(100, (int) ($_GET['limit'] ?? 50));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare("
            SELECT p.*, c.nombre_cuadrilla, u.nombre as usuario_nombre
            FROM partes_diarios p
            LEFT JOIN cuadrillas c ON p.id_cuadrilla = c.id_cuadrilla
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE {$whereClause}
            ORDER BY p.fecha DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT p.*, c.nombre_cuadrilla, u.nombre as usuario_nombre
            FROM partes_diarios p
            LEFT JOIN cuadrillas c ON p.id_cuadrilla = c.id_cuadrilla
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_parte = ?
        ");
        $stmt->execute([$id]);
        $parte = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$parte) {
            Response::json(['error' => 'ERR-PART-404', 'message' => 'Parte no encontrado'], 404);
            return;
        }

        // Get detail items (ODTs worked, materials consumed, etc.)
        $items = $pdo->prepare("SELECT * FROM partes_detalle WHERE id_parte = ?");
        $items->execute([$id]);
        $parte['detalles'] = $items->fetchAll(\PDO::FETCH_ASSOC);

        Response::json(['data' => $parte]);
    }

    public static function store(): void
    {
        $auth = AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['id_cuadrilla']) || empty($body['fecha'])) {
            Response::json(['error' => 'ERR-PART-FIELD', 'message' => 'id_cuadrilla y fecha requeridos'], 400);
            return;
        }

        $now = Database::isPostgres() ? 'CURRENT_TIMESTAMP' : 'NOW()';

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO partes_diarios (id_cuadrilla, fecha, id_usuario, observaciones, created_at)
                VALUES (?, ?, ?, ?, {$now})
            ");
            $stmt->execute([
                (int) $body['id_cuadrilla'],
                $body['fecha'],
                $auth['id'] ?? 1,
                $body['observaciones'] ?? null,
            ]);
            $parteId = $pdo->lastInsertId();

            // Insert details if provided
            if (!empty($body['detalles']) && is_array($body['detalles'])) {
                $stmtDet = $pdo->prepare("
                    INSERT INTO partes_detalle (id_parte, id_odt, tipo, descripcion, cantidad, unidad)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($body['detalles'] as $det) {
                    $stmtDet->execute([
                        $parteId,
                        $det['id_odt'] ?? null,
                        $det['tipo'] ?? 'trabajo',
                        $det['descripcion'] ?? null,
                        (float) ($det['cantidad'] ?? 0),
                        $det['unidad'] ?? null,
                    ]);
                }
            }

            $pdo->commit();
            Response::json(['message' => 'Parte creado', 'id' => $parteId], 201);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
