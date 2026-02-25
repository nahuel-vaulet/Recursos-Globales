<?php
/**
 * [!] ARCH: DashboardController — KPIs, alertas, tendencias
 * [✓] AUDIT: Migrado desde api/dashboard.php con dual-mode SQL
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class DashboardController
{
    // ─── GET /api/dashboard/stats ────────────────────────

    public static function stats(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $curDate = Database::isPostgres() ? 'CURRENT_DATE' : 'CURDATE()';

        // Total materials
        $totalMat = (int) $pdo->query("SELECT COUNT(*) FROM materiales WHERE estado = 1")->fetchColumn();

        // Total stock value
        $stockTotal = (float) $pdo->query("SELECT COALESCE(SUM(stock_actual), 0) FROM materiales WHERE estado = 1")->fetchColumn();

        // Low stock alerts
        $alertas = (int) $pdo->query("SELECT COUNT(*) FROM materiales WHERE stock_actual <= stock_minimo AND estado = 1")->fetchColumn();

        // Today's movements
        if (Database::isPostgres()) {
            $movHoy = (int) $pdo->query("SELECT COUNT(*) FROM movimientos WHERE fecha::date = CURRENT_DATE")->fetchColumn();
        } else {
            $movHoy = (int) $pdo->query("SELECT COUNT(*) FROM movimientos WHERE DATE(fecha) = CURDATE()")->fetchColumn();
        }

        // Active squads
        $cuadrillas = (int) $pdo->query("SELECT COUNT(*) FROM cuadrillas WHERE estado_operativo = 'Activa'")->fetchColumn();

        // Monthly consumption
        if (Database::isPostgres()) {
            $thisMonth = (float) $pdo->query("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos WHERE tipo = 'salida' AND EXTRACT(MONTH FROM fecha) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM fecha) = EXTRACT(YEAR FROM CURRENT_DATE)")->fetchColumn();
            $lastMonth = (float) $pdo->query("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos WHERE tipo = 'salida' AND EXTRACT(MONTH FROM fecha) = EXTRACT(MONTH FROM CURRENT_DATE - INTERVAL '1 month') AND EXTRACT(YEAR FROM fecha) = EXTRACT(YEAR FROM CURRENT_DATE - INTERVAL '1 month')")->fetchColumn();
        } else {
            $thisMonth = (float) $pdo->query("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos WHERE tipo = 'salida' AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())")->fetchColumn();
            $lastMonth = (float) $pdo->query("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos WHERE tipo = 'salida' AND MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
        }

        $change = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        Response::json([
            'data' => [
                'total_materiales' => $totalMat,
                'stock_total' => $stockTotal,
                'alertas_count' => $alertas,
                'movimientos_hoy' => $movHoy,
                'cuadrillas_activas' => $cuadrillas,
                'consumo_mensual' => $thisMonth,
                'variacion_mensual' => $change,
            ],
        ]);
    }

    // ─── GET /api/dashboard/alerts ──────────────────────

    public static function alerts(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT id, nombre, codigo, unidad_medida, stock_actual, stock_minimo,
                   ROUND((stock_actual / NULLIF(stock_minimo, 0)) * 100, 1) as porcentaje
            FROM materiales 
            WHERE stock_actual <= stock_minimo AND estado = 1
            ORDER BY (stock_actual / NULLIF(stock_minimo, 0)) ASC
            LIMIT 10
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/dashboard/recent ──────────────────────

    public static function recent(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $limit = min(20, max(1, (int) ($_GET['limit'] ?? 5)));

        $stmt = $pdo->prepare("
            SELECT m.id, m.tipo, m.cantidad, m.fecha, m.observaciones,
                   mat.nombre as material_nombre, mat.unidad_medida,
                   c.nombre_cuadrilla,
                   u.nombre as usuario_nombre
            FROM movimientos m
            JOIN materiales mat ON m.material_id = mat.id
            LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id_cuadrilla
            LEFT JOIN usuarios u ON m.usuario_id = u.id_usuario
            ORDER BY m.fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/dashboard/consumption ─────────────────

    public static function consumption(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        if (Database::isPostgres()) {
            $dateInterval = "fecha >= CURRENT_DATE - INTERVAL '30 days'";
        } else {
            $dateInterval = "fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }

        $stmt = $pdo->query("
            SELECT c.id_cuadrilla as id, c.nombre_cuadrilla as nombre,
                   COALESCE(SUM(CASE WHEN m.tipo = 'salida' THEN m.cantidad ELSE 0 END), 0) as total_consumo
            FROM cuadrillas c
            LEFT JOIN movimientos m ON c.id_cuadrilla = m.cuadrilla_id AND m.{$dateInterval}
            WHERE c.estado_operativo = 'Activa'
            GROUP BY c.id_cuadrilla, c.nombre_cuadrilla
            ORDER BY total_consumo DESC
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/dashboard/trends ──────────────────────

    public static function trends(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        if (Database::isPostgres()) {
            $stmt = $pdo->query("
                SELECT TO_CHAR(fecha, 'YYYY-MM') as mes,
                       TO_CHAR(fecha, 'Mon YYYY') as mes_label,
                       SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
                       SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas
                FROM movimientos
                WHERE fecha >= CURRENT_DATE - INTERVAL '6 months'
                GROUP BY TO_CHAR(fecha, 'YYYY-MM'), TO_CHAR(fecha, 'Mon YYYY')
                ORDER BY mes ASC
            ");
        } else {
            $stmt = $pdo->query("
                SELECT DATE_FORMAT(fecha, '%Y-%m') as mes,
                       DATE_FORMAT(fecha, '%b %Y') as mes_label,
                       SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
                       SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas
                FROM movimientos
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m'), DATE_FORMAT(fecha, '%b %Y')
                ORDER BY mes ASC
            ");
        }

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }
}
