<?php
/**
 * [!] ARCH: API REST para ODTs - Sincronización PWA
 * [→] EDITAR: Ajustar CORS según entorno de producción
 * [✓] AUDIT: Endpoints GET, POST, PUT, DELETE con JSON
 */

// [→] EDITAR: Headers CORS para acceso desde dispositivos móviles
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// [✓] AUDIT: Verificar sesión para API
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($method) {

        // [✓] AUDIT: GET - Obtener ODTs
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM odt_maestro WHERE id_odt = ?");
                $stmt->execute([$id]);
                $odt = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$odt) {
                    http_response_code(404);
                    echo json_encode(['error' => 'ODT no encontrada']);
                    exit;
                }

                echo json_encode($odt);
            } else {
                $sql = "SELECT o.*, t.nombre as tipo_trabajo 
                        FROM odt_maestro o
                        LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                        ORDER BY o.created_at DESC";
                $odts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($odts);
            }
            break;

        // [✓] AUDIT: POST - Crear ODT
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            // Validación
            if (empty($data['nro_odt_assa']) || empty($data['direccion'])) {
                http_response_code(400);
                echo json_encode(['error' => 'nro_odt_assa y direccion son obligatorios']);
                exit;
            }

            // Verificar duplicado
            $checkStmt = $pdo->prepare("SELECT id_odt FROM odt_maestro WHERE nro_odt_assa = ?");
            $checkStmt->execute([$data['nro_odt_assa']]);
            if ($checkStmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'El número de ODT ya existe']);
                exit;
            }

            $sql = "INSERT INTO odt_maestro 
                    (nro_odt_assa, direccion, id_tipologia, prioridad, estado_gestion,
                     fecha_inicio_plazo, fecha_vencimiento, avance, inspector)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nro_odt_assa'],
                $data['direccion'],
                $data['id_tipologia'] ?? null,
                $data['prioridad'] ?? 'Normal',
                $data['estado_gestion'] ?? 'Sin Programar',
                $data['fecha_inicio_plazo'] ?? null,
                $data['fecha_vencimiento'] ?? null,
                $data['avance'] ?? '',
                $data['inspector'] ?? ''
            ]);

            $newId = $pdo->lastInsertId();
            registrarAccion('CREAR', 'odt_maestro', "ODT creada via API: " . $data['nro_odt_assa'], $newId);

            http_response_code(201);
            echo json_encode(['id' => $newId, 'message' => 'ODT creada']);
            break;

        // [✓] AUDIT: PUT - Actualizar ODT
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Verificar existencia
            $checkStmt = $pdo->prepare("SELECT id_odt FROM odt_maestro WHERE id_odt = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'ODT no encontrada']);
                exit;
            }

            $sql = "UPDATE odt_maestro SET 
                    nro_odt_assa = COALESCE(?, nro_odt_assa),
                    direccion = COALESCE(?, direccion),
                    id_tipologia = ?,
                    prioridad = COALESCE(?, prioridad),
                    estado_gestion = COALESCE(?, estado_gestion),
                    fecha_inicio_plazo = ?,
                    fecha_vencimiento = ?,
                    avance = COALESCE(?, avance),
                    inspector = COALESCE(?, inspector)
                    WHERE id_odt = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nro_odt_assa'] ?? null,
                $data['direccion'] ?? null,
                $data['id_tipologia'] ?? null,
                $data['prioridad'] ?? null,
                $data['estado_gestion'] ?? null,
                $data['fecha_inicio_plazo'] ?? null,
                $data['fecha_vencimiento'] ?? null,
                $data['avance'] ?? null,
                $data['inspector'] ?? null,
                $id
            ]);

            registrarAccion('EDITAR', 'odt_maestro', "ODT editada via API", $id);

            echo json_encode(['message' => 'ODT actualizada']);
            break;

        // [✓] AUDIT: DELETE - Eliminar ODT
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }

            $infoStmt = $pdo->prepare("SELECT nro_odt_assa FROM odt_maestro WHERE id_odt = ?");
            $infoStmt->execute([$id]);
            $nro = $infoStmt->fetchColumn();

            if (!$nro) {
                http_response_code(404);
                echo json_encode(['error' => 'ODT no encontrada']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM odt_maestro WHERE id_odt = ?");
            $stmt->execute([$id]);

            registrarAccion('ELIMINAR', 'odt_maestro', "ODT eliminada via API: $nro", $id);

            echo json_encode(['message' => 'ODT eliminada']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }

} catch (PDOException $e) {
    error_log("Error en api/odt.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>