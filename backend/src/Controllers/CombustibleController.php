<?php
/**
 * [!] ARCH: CombustibleController — Gestión de combustibles
 * [✓] AUDIT: Carga, despacho, historial consumo
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class CombustibleController
{
    // ─── GET /api/combustibles/stock ────────────────────

    public static function stock(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT tc.*, 
                   ROUND((tc.stock_actual / NULLIF(tc.capacidad_maxima, 0)) * 100, 1) as porcentaje_lleno
            FROM tanques_combustible tc
            WHERE tc.estado = 1
            ORDER BY tc.nombre ASC
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/combustibles/historial ────────────────

    public static function historial(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['vehiculo_id'])) {
            $where[] = "hc.id_vehiculo = :veh";
            $params['veh'] = (int) $_GET['vehiculo_id'];
        }

        if (!empty($_GET['tipo'])) {
            $where[] = "hc.tipo_movimiento = :tipo";
            $params['tipo'] = $_GET['tipo'];
        }

        if (!empty($_GET['fecha_desde'])) {
            $where[] = "hc.fecha >= :f_desde";
            $params['f_desde'] = $_GET['fecha_desde'];
        }

        $whereClause = implode(' AND ', $where);
        $limit = min(100, (int) ($_GET['limit'] ?? 50));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare("
            SELECT hc.*, v.patente, v.marca,
                   tc.nombre as tanque_nombre,
                   u.nombre as usuario_nombre
            FROM historial_combustible hc
            LEFT JOIN vehiculos v ON hc.id_vehiculo = v.id_vehiculo
            LEFT JOIN tanques_combustible tc ON hc.id_tanque = tc.id_tanque
            LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario
            WHERE {$whereClause}
            ORDER BY hc.fecha DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── POST /api/combustibles/carga ───────────────────

    public static function carga(): void
    {
        $auth = AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['id_tanque']) || empty($body['litros'])) {
            Response::json(['error' => 'ERR-FUEL-FIELD', 'message' => 'id_tanque y litros requeridos'], 400);
            return;
        }

        $litros = (float) $body['litros'];
        $idTanque = (int) $body['id_tanque'];

        $pdo->beginTransaction();
        try {
            // Update tank
            $pdo->prepare("UPDATE tanques_combustible SET stock_actual = stock_actual + ? WHERE id_tanque = ?")->execute([$litros, $idTanque]);

            // Register history
            $now = Database::isPostgres() ? 'CURRENT_TIMESTAMP' : 'NOW()';
            $pdo->prepare("
                INSERT INTO historial_combustible (id_tanque, tipo_movimiento, litros, id_usuario, fecha, observaciones)
                VALUES (?, 'carga', ?, ?, {$now}, ?)
            ")->execute([
                        $idTanque,
                        $litros,
                        $auth['id'] ?? 1,
                        $body['observaciones'] ?? null,
                    ]);

            $pdo->commit();
            Response::json(['message' => 'Carga registrada'], 201);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ─── POST /api/combustibles/despacho ────────────────

    public static function despacho(): void
    {
        $auth = AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['id_vehiculo']) || empty($body['litros'])) {
            Response::json(['error' => 'ERR-FUEL-FIELD', 'message' => 'id_vehiculo y litros requeridos'], 400);
            return;
        }

        $litros = (float) $body['litros'];
        $idVehiculo = (int) $body['id_vehiculo'];
        $idTanque = !empty($body['id_tanque']) ? (int) $body['id_tanque'] : null;

        $pdo->beginTransaction();
        try {
            // Decrease tank stock if from tank
            if ($idTanque) {
                $stmt = $pdo->prepare("SELECT stock_actual FROM tanques_combustible WHERE id_tanque = ?");
                $stmt->execute([$idTanque]);
                $tank = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$tank || $tank['stock_actual'] < $litros) {
                    $pdo->rollBack();
                    Response::json(['error' => 'ERR-FUEL-STOCK', 'message' => 'Stock insuficiente en tanque'], 400);
                    return;
                }

                $pdo->prepare("UPDATE tanques_combustible SET stock_actual = stock_actual - ? WHERE id_tanque = ?")->execute([$litros, $idTanque]);
            }

            // Register history
            $now = Database::isPostgres() ? 'CURRENT_TIMESTAMP' : 'NOW()';
            $pdo->prepare("
                INSERT INTO historial_combustible (id_tanque, id_vehiculo, tipo_movimiento, litros, km_odometro, id_usuario, fecha, observaciones)
                VALUES (?, ?, 'despacho', ?, ?, ?, {$now}, ?)
            ")->execute([
                        $idTanque,
                        $idVehiculo,
                        $litros,
                        $body['km_odometro'] ?? null,
                        $auth['id'] ?? 1,
                        $body['observaciones'] ?? null,
                    ]);

            $pdo->commit();
            Response::json(['message' => 'Despacho registrado'], 201);
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
