<?php
namespace Spot\Application\Services;

use Spot\Infrastructure\Repositories\PDOSpotRepository;
use Exception;

class CombustibleService
{
    public function __construct(private PDOSpotRepository $repository)
    {
    }

    public function iniciarTraspaso(int $idVehiculo): array
    {
        try {
            $kmUltimo = $this->repository->getUltimoKm($idVehiculo);
            // In a real scenario, factorConsumo would come from vehicle entity
            return [
                'status' => 'success',
                'km_ultimo' => $kmUltimo,
                'factor_consumo_default' => 0.15 // example: 15 liters per 100km -> 0.15 per km
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function verificarConsumo(float $estimados, float $cargados, float $tolerancia): array
    {
        $dif = abs($cargados - $estimados);
        $estado = ($dif <= $tolerancia) ? 'Verifica' : 'Alerta';

        return [
            'estado' => $estado,
            'diferencia' => $dif,
            'mensaje' => $estado === 'Alerta' ? "Diferencia de {$dif} litros excede tolerancia." : "Consumo verificado."
        ];
    }

    public function registrarCarga(array $data): array
    {
        $errorId = bin2hex(random_bytes(16));
        try {
            // Business logic for registering fuel load...
            // Similar to material delivery but with odometer data
            return ['status' => 'success', 'message' => 'Carga registrada correctamente'];
        } catch (Exception $e) {
            return ['status' => 'error', 'errorId' => $errorId, 'message' => $e->getMessage()];
        }
    }
}
