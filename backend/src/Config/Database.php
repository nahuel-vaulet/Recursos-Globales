<?php
/**
 * [!] ARCH: Database Configuration — Dual Mode (Local MySQL / Production PostgreSQL)
 * [✓] AUDIT: Detects DATABASE_URL env for Supabase, falls back to XAMPP MySQL
 * [→] EDITAR: Set DATABASE_URL in Render environment variables
 *
 * Production: Parses DATABASE_URL (postgresql://user:pass@host:port/dbname)
 * Local:      Connects to MySQL on XAMPP (default: root@localhost/erp_global)
 */

declare(strict_types=1);

namespace App\Config;

class Database
{
    private static ?\PDO $instance = null;
    private static string $driver = '';

    /**
     * Returns a singleton PDO connection.
     * Automatically detects environment via DATABASE_URL.
     */
    public static function getConnection(): \PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');

        if ($databaseUrl) {
            // ─── PRODUCCIÓN (Render → Supabase PostgreSQL) ──────
            self::$driver = 'pgsql';
            $opts = parse_url($databaseUrl);

            $host = $opts['host'] ?? 'localhost';
            $port = $opts['port'] ?? 6543;
            $user = $opts['user'] ?? 'postgres';
            $pass = $opts['pass'] ?? '';
            $dbname = ltrim($opts['path'] ?? '/postgres', '/');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        } else {
            // ─── LOCAL (XAMPP → MySQL) ──────────────────────────
            self::$driver = 'mysql';
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $dbname = $_ENV['DB_NAME'] ?? 'erp_global';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        }

        try {
            self::$instance = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return self::$instance;

        } catch (\PDOException $e) {
            error_log('[ERR-DB-CONN] Database connection failed: ' . $e->getMessage());

            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'ERR-DB-503',
                'message' => 'Database service unavailable',
                'driver' => self::$driver,
            ]);
            exit;
        }
    }

    /**
     * Get current database driver ('mysql' or 'pgsql').
     * Useful for writing driver-specific queries during migration.
     */
    public static function getDriver(): string
    {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$driver;
    }

    /**
     * Check if running on PostgreSQL (production).
     */
    public static function isPostgres(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    /**
     * Reset connection (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$driver = '';
    }
}
