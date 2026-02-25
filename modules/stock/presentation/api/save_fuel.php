<?php
header('Content-Type: application/json');
require_once '../../../../config/database.php';
require_once '../../domain/entities/Remito.php';
require_once '../../domain/entities/TraspasoCombustible.php';
require_once '../../infrastructure/repositories/PDOStockRepository.php';
require_once '../../application/services/StockFuelService.php';

use Stock\Infrastructure\Repositories\PDOStockRepository;
use Stock\Application\Services\StockFuelService;

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data)
        throw new Exception("Sin datos");

    $repo = new PDOStockRepository($pdo);
    $service = new StockFuelService($repo);

    // Ensure session user if possible
    $data['usuario_sistema_id'] = $_SESSION['user_id'] ?? 1;

    $result = $service->registrarCarga($data);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
