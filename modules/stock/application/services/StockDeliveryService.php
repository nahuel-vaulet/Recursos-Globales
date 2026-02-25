<?php
namespace Stock\Application\Services;

use Stock\Domain\Entities\Remito;
use Stock\Infrastructure\Repositories\PDOStockRepository;
use Exception;

class StockDeliveryService
{
    public function __construct(private PDOStockRepository $repository)
    {
    }

    public function createRemitoMultiple(array $data): array
    {
        $errorId = bin2hex(random_bytes(16));
        try {
            if (empty($data['items'])) {
                throw new Exception("Debe seleccionar al menos un material.");
            }
            if (empty($data['id_personal_entrega']) || empty($data['id_personal_recepcion'])) {
                throw new Exception("Personal de entrega y recepción son obligatorios.");
            }

            $remito = new Remito(
                id: null,
                nroRemito: 'REM-STK-' . time(),
                tipo: 'Material',
                idCuadrillaOrigen: null, // Usually from central office
                idCuadrillaDestino: $data['id_cuadrilla_destino'] ?? null,
                idProveedor: null,
                idPersonalEntrega: $data['id_personal_entrega'],
                idPersonalRecepcion: $data['id_personal_recepcion'],
                destinoObra: $data['destino_obra'] ?? null,
                fechaEmision: date('Y-m-d H:i:s'),
                usuarioSistemaId: $data['usuario_sistema_id'] ?? 1,
                items: $data['items']
            );

            $id = $this->repository->saveRemito($remito);

            return [
                'status' => 'success',
                'id_remito' => $id,
                'nro_remito' => $remito->nroRemito
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'details' => 'StockDeliveryService::createRemitoMultiple'
            ];
        }
    }
}
