<?php
header('Content-Type: application/json');
require_once '../../../config/database.php';
require_once '../infrastructure/repositories/PDOSpotRepository.php';
require_once '../application/services/CombustibleService.php';

use Spot\Infrastructure\Repositories\PDOSpotRepository;
use Spot\Application\Services\CombustibleService;

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data)
        throw new Exception("Invalid input");

    $repository = new PDOSpotRepository($pdo);
    $service = new CombustibleService($repository);

    // Business Logic call
    $result = $service->registrarCarga($data);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'errorId' => 'API-FUEL-' . bin2hex(random_bytes(4)),
        'message' => $e->getMessage()
    ]);
}
