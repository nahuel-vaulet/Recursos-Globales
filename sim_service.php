<?php
require_once 'config/database.php';
require_once 'modules/stock/domain/entities/Remito.php';
require_once 'modules/stock/domain/entities/TraspasoCombustible.php';
require_once 'modules/stock/infrastructure/repositories/PDOStockRepository.php';
require_once 'modules/stock/application/services/StockFuelService.php';

use Stock\Infrastructure\Repositories\PDOStockRepository;
use Stock\Application\Services\StockFuelService;

try {
    $repo = new PDOStockRepository($pdo);
    $service = new StockFuelService($repo);

    $data = [
        'id_cuadrilla_recepcion' => 1,
        'litros_cargados' => 5.5,
        'id_tanque' => 1,
        'id_vehiculo' => 1,
        'km_ultimo' => 100,
        'km_actual' => 150,
        'id_personal_entrega' => 1,
        'id_personal_recepcion' => 1,
        'usuario_sistema_id' => 1
    ];

    echo "Running registrarCarga...\n";
    $result = $service->registrarCarga($data);
    echo json_encode($result);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>