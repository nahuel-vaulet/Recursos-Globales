<?php
/**
 * [!] ARCH: ErrorService — Servicio de diagnóstico y logging de errores
 * Genera ID únicos, Error Stack simplificado, y registra en memoria/endpoint.
 */

class ErrorService
{
    /** @var array Cola de errores en memoria */
    private static array $errorLog = [];

    /**
     * Genera un ID de error único
     * Formato: ERR-{TIMESTAMP}-{RANDOM4}
     */
    public static function generateId(): string
    {
        return 'ERR-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    /**
     * Captura una excepción y genera Error Stack
     *
     * @param \Throwable $exception La excepción capturada
     * @param string $component Componente afectado (Router, Controller, Service, Repository, View)
     * @param string $operation Operación que falló
     * @param array $inputResumen Resumen de inputs relevantes (sanitizado)
     * @param string $contexto Tag de contexto opcional (ej: 'stockform')
     * @param string $motivo Motivo opcional (ej: 'DB_UNAVAILABLE')
     * @return array Error Stack completo
     */
    public static function capture(
        \Throwable $exception,
        string $component,
        string $operation,
        array $inputResumen = [],
        string $contexto = '',
        string $motivo = ''
    ): array {
        $errorId = self::generateId();

        $errorStack = [
            'id' => $errorId,
            'component' => $component,
            'operation' => $operation,
            'input_resumen' => self::sanitizeInput($inputResumen),
            'message' => $exception->getMessage(),
            'trace' => self::truncateTrace($exception),
            'timestamp' => date('Y-m-d H:i:s'),
            'contexto' => $contexto,
            'motivo' => $motivo,
            'http_code' => self::resolveHttpCode($exception),
        ];

        // Registrar en memoria
        self::$errorLog[] = $errorStack;

        // Registrar en error_log de PHP
        error_log("[{$errorId}] [{$component}] [{$operation}] " . $exception->getMessage());

        return $errorStack;
    }

    /**
     * Captura específica de PDOException con contexto de formulario
     */
    public static function captureDbError(
        \PDOException $exception,
        string $contexto,
        array $inputResumen = []
    ): array {
        return self::capture(
            $exception,
            'Repository',
            'database_query',
            $inputResumen,
            $contexto,
            'DB_UNAVAILABLE'
        );
    }

    /**
     * Envía respuesta JSON de error al cliente
     */
    public static function respondWithError(array $errorStack, int $httpCode = 500): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => $errorStack['id'],
                'message' => $errorStack['message'],
                'component' => $errorStack['component'],
                'operation' => $errorStack['operation'],
            ],
            'user_message' => self::getUserFriendlyMessage($errorStack),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Genera HTML del modal de error (no bloqueante)
     */
    public static function renderErrorModal(array $errorStack): string
    {
        $json = htmlspecialchars(json_encode($errorStack, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $id = htmlspecialchars($errorStack['id']);
        $msg = htmlspecialchars($errorStack['message']);
        $comp = htmlspecialchars($errorStack['component']);
        $op = htmlspecialchars($errorStack['operation']);

        return <<<HTML
        <div id="errorModal_{$id}" class="error-modal-overlay" style="display:flex;">
            <div class="error-modal-content">
                <div class="error-modal-header">
                    <span>⚠️ Error: {$id}</span>
                    <button onclick="document.getElementById('errorModal_{$id}').remove()" class="error-modal-close">&times;</button>
                </div>
                <div class="error-modal-body">
                    <p><strong>Componente:</strong> {$comp}</p>
                    <p><strong>Operación:</strong> {$op}</p>
                    <p><strong>Mensaje:</strong> {$msg}</p>
                    <pre class="error-stack-pre">{$json}</pre>
                </div>
                <div class="error-modal-footer">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('errStack_{$id}').textContent).then(()=>alert('Copiado'))" class="btn btn-outline">
                        📋 Copiar Error Stack
                    </button>
                </div>
                <textarea id="errStack_{$id}" style="display:none;">{$json}</textarea>
            </div>
        </div>
        HTML;
    }

    /**
     * Obtiene el log de errores en memoria
     */
    public static function getLog(): array
    {
        return self::$errorLog;
    }

    /**
     * Envía errores en memoria al endpoint /api/logs.php si hay conexión
     */
    public static function flushToEndpoint(string $endpoint = '/APP-Prueba/api/logs.php'): bool
    {
        if (empty(self::$errorLog)) {
            return true;
        }

        // En entorno servidor no hacemos fetch; esto se usa desde JS.
        // Registramos en archivo de log local.
        $logFile = __DIR__ . '/../logs/error_service.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        foreach (self::$errorLog as $err) {
            @file_put_contents($logFile, json_encode($err) . "\n", FILE_APPEND);
        }

        self::$errorLog = [];
        return true;
    }

    // --- Helpers privados ---

    private static function sanitizeInput(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            if (is_string($value) && strlen($value) > 100) {
                $sanitized[$key] = substr($value, 0, 100) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    private static function truncateTrace(\Throwable $e): string
    {
        $trace = $e->getTraceAsString();
        $lines = explode("\n", $trace);
        return implode("\n", array_slice($lines, 0, 5));
    }

    private static function resolveHttpCode(\Throwable $e): int
    {
        if ($e instanceof \PDOException) {
            return 503;
        }
        if ($e instanceof \InvalidArgumentException) {
            return 400;
        }
        return 500;
    }

    private static function getUserFriendlyMessage(array $errorStack): string
    {
        if ($errorStack['motivo'] === 'DB_UNAVAILABLE') {
            return 'El servidor de base de datos no está disponible en este momento. Por favor intentá de nuevo en unos minutos.';
        }
        return 'Ocurrió un error inesperado. Si persiste, copiá el Error Stack y contactá al administrador.';
    }
}
