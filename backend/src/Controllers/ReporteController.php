<?php
/**
 * [!] ARCH: ReporteController — Analítica y métricas
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class ReporteController
{
    public static function odtEfficiency(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        // ODTs completed vs Pending
        $stmt = $pdo->query("
            SELECT estado, COUNT(*) as cantidad 
            FROM odt 
            GROUP BY estado
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function consumption(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        // Material consumption in the last 30 days
        $where = Database::isPostgres() ? "fecha >= CURRENT_DATE - INTERVAL '30 days'" : "fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $stmt = $pdo->query("
            SELECT m.nombre, SUM(mov.cantidad) as total_consumido
            FROM movimientos mov
            JOIN materiales m ON mov.material_id = m.id
            WHERE mov.tipo = 'salida' AND {$where}
            GROUP BY m.nombre
            ORDER BY total_consumido DESC
            LIMIT 10
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }
}
