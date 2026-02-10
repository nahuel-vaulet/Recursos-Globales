<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp_stock');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('JWT_SECRET', 'erp_stock_secret_key_2026');
define('JWT_EXPIRATION', 86400);

date_default_timezone_set('America/Argentina/Buenos_Aires');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexion a base de datos: ' . $e->getMessage()]);
            exit();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null)
            self::$instance = new self();
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

function getDB()
{
    return Database::getInstance()->getConnection();
}
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit();
}
function jsonError($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(['error' => true, 'message' => $message]);
    exit();
}
function getRequestBody()
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
function getMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}
function getParam($key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function generateJWT($payload)
{
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload['exp'] = time() + JWT_EXPIRATION;
    $payload = base64_encode(json_encode($payload));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

function verifyJWT($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return false;
    list($header, $payload, $signature) = $parts;
    $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if ($signature !== $expectedSignature)
        return false;
    $payload = json_decode(base64_decode($payload), true);
    if ($payload['exp'] < time())
        return false;
    return $payload;
}

function requireAuth()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches))
        jsonError('Token no proporcionado', 401);
    $payload = verifyJWT($matches[1]);
    if (!$payload)
        jsonError('Token invalido o expirado', 401);
    return $payload;
}
