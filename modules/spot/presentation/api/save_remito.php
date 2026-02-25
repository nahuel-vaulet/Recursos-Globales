<?php
header('Content-Type: application/json');
require_once '../../../config/database.php';
require_once '../infrastructure/repositories/PDOSpotRepository.php';
require_once '../application/services/EntregasMaterialService.php';
require_once '../../personal/auth.php'; // For session context if needed

use Spot\Infrastructure\Repositories\PDOSpotRepository;
use Spot\Application\Services\EntregasMaterialService;

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    // Dependency Injection (Minimalist)
    $repository = new PDOSpotRepository($pdo);
    $service = new EntregasMaterialService($repository);

    // Context - assuming session exists via header.php requirement or auth.php
    $data['usuario_sistema_id'] = 1; // Placeholder for real session user_id

    $result = $service->createRemitoMultiple($data);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'errorId' => 'API-' . bin2hex(random_bytes(4)),
        'message' => $e->getMessage()
    ]);
}
