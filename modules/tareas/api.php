<?php
// modules/tareas/api.php
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
if (!verificarSesion(false)) {
    http_response_code(401);
    echo json_encode(["message" => "No autorizado"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Auto-migration check (Fail-safe)
try {
    $check = $pdo->query("SHOW TABLES LIKE 'tareas'");
    if ($check->rowCount() == 0) {
        $sql = "
        CREATE TABLE IF NOT EXISTS tareas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT DEFAULT 1,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT,
            fecha_limite DATE,
            prioridad ENUM('Alta', 'Media', 'Baja') DEFAULT 'Media',
            estado ENUM('Pendiente', 'En progreso', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
            categoria VARCHAR(50) DEFAULT 'Otros',
            recordatorio_especial BOOLEAN DEFAULT FALSE,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            responsable VARCHAR(100) DEFAULT 'Cache',
            fecha_completada DATETIME DEFAULT NULL,
            id_usuario_creador INT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    // Silent fail or log
}

// Router
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getTask($pdo, $_GET['id']);
        } else {
            getTasks($pdo);
        }
        break;
    case 'POST':
        if ($action === 'purge') {
            purgeTasks($pdo);
        } else {
            createTask($pdo);
        }
        break;
    case 'PUT':
        updateTask($pdo);
        break;
    case 'DELETE':
        deleteTask($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

function getTasks($pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM tareas ORDER BY fecha_creacion DESC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function getTask($pdo, $id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($task) {
            echo json_encode($task);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Tarea no encontrada"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function createTask($pdo)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['titulo'])) {
        http_response_code(400);
        echo json_encode(["message" => "El título es requerido"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tareas (usuario_id, titulo, descripcion, fecha_limite, prioridad, estado, responsable, id_usuario_creador) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $usuario = obtenerUsuarioActual();

        $stmt->execute([
            $usuario['id'] ?? 1,
            $data['titulo'],
            $data['descripcion'] ?? '',
            $data['fecha_limite'] ?? null,
            $data['prioridad'] ?? 'Baja',
            $data['estado'] ?? 'Pendiente',
            $data['responsable'] ?? 'Cache',
            $usuario['id'] ?? null
        ]);

        $id = $pdo->lastInsertId();
        getTask($pdo, $id);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function updateTask($pdo)
{
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(["message" => "ID requerido"]);
        exit;
    }

    try {
        $fields = [];
        $values = [];

        // Dynamic update build
        $allowed = ['titulo', 'descripcion', 'fecha_limite', 'prioridad', 'estado', 'responsable'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        // Logic for completion date
        if (isset($data['estado'])) {
            if ($data['estado'] === 'Completada') {
                $fields[] = "fecha_completada = NOW()";
            } else {
                $fields[] = "fecha_completada = NULL";
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "Nada que actualizar"]);
            exit;
        }

        $values[] = $id;
        $sql = "UPDATE tareas SET " . implode(", ", $fields) . " WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        getTask($pdo, $id);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function deleteTask($pdo)
{
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(["message" => "ID requerido"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "Tarea eliminada"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function purgeTasks($pdo)
{
    $data = json_decode(file_get_contents("php://input"), true);
    $date = $data['date'] ?? null;

    if (!$date) {
        http_response_code(400);
        echo json_encode(["message" => "Fecha requerida"]);
        exit;
    }

    try {
        // 1. Select for CSV
        $stmt = $pdo->prepare("SELECT * FROM tareas WHERE estado IN ('Completada', 'Cancelada') AND fecha_limite <= ?");
        $stmt->execute([$date]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tasks)) {
            echo json_encode(["deleted_count" => 0, "csv_data" => null]);
            return;
        }

        // 2. Generate CSV
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, array_keys($tasks[0])); // Header
        foreach ($tasks as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        // 3. Delete
        $stmt = $pdo->prepare("DELETE FROM tareas WHERE estado IN ('Completada', 'Cancelada') AND fecha_limite <= ?");
        $stmt->execute([$date]);

        echo json_encode([
            "deleted_count" => $stmt->rowCount(),
            "csv_data" => base64_encode($csv)
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>