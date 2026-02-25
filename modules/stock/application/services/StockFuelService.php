<?php
namespace Stock\Application\Services;

use Stock\Infrastructure\Repositories\PDOStockRepository;
use Stock\Domain\Entities\Remito;
use Exception;

class StockFuelService
{
    public function __construct(private PDOStockRepository $repository)
    {
    }

    public function registrarCarga(array $data): array
    {
        $errorId = bin2hex(random_bytes(16));
        try {
            $this->repository->beginTransaction();

            // 1. Create Remito Header
            $remito = new Remito(
                id: null,
                nroRemito: 'REM-COMB-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
                tipo: 'Combustible',
                idCuadrillaOrigen: null,
                idCuadrillaDestino: $data['id_cuadrilla_recepcion'] ?? null,
                idProveedor: $data['id_proveedor'] ?? null,
                idPersonalEntrega: $data['id_personal_entrega'] ?? 1,
                idPersonalRecepcion: $data['id_personal_recepcion'] ?? 1,
                destinoObra: null,
                fechaEmision: date('Y-m-d H:i:s'),
                usuarioSistemaId: $data['usuario_sistema_id'] ?? 1,
                items: [['id_material' => 999, 'cantidad' => (float) $data['litros_cargados']]]
            );

            $idRemito = $this->repository->saveRemito($remito);

            // 2. Save Odometer / Verification Data
            $data['nro_remito'] = $remito->nroRemito; // Consolidate remito number
            $data['fecha_hora'] = $remito->fechaEmision; // Sync with remito date
            $this->repository->saveFuelTransfer($idRemito, $data);

            $this->repository->commit();

            return [
                'status' => 'success',
                'id_remito' => $idRemito,
                'nro_remito' => $remito->nroRemito
            ];

        } catch (Exception $e) {
            $this->repository->rollBack();
            return [
                'status' => 'error',
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'details' => 'StockFuelService::registrarCarga'
            ];
        }
    }

    public function recordReplenishment(array $data): array
    {
        try {
            if (empty($data['id_tanque']) || empty($data['litros'])) {
                throw new Exception("Datos incompletos para el abastecimiento.");
            }

            $this->repository->replenishTank(
                (int) $data['id_tanque'],
                (float) $data['litros'],
                (int) ($data['usuario_id'] ?? 1)
            );

            return [
                'status' => 'success',
                'message' => 'Abastecimiento registrado correctamente.'
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
