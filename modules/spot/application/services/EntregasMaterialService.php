<?php
namespace Spot\Application\Services;

use Spot\Domain\Entities\Remito;
use Spot\Infrastructure\Repositories\PDOSpotRepository;
use Exception;

class EntregasMaterialService
{
    public function __construct(private PDOSpotRepository $repository)
    {
    }

    public function createRemitoMultiple(array $data): array
    {
        $errorId = $this->generateUuid();
        try {
            // Validation
            if (empty($data['items'])) {
                throw new Exception("Debe seleccionar al menos un material.");
            }
            if (empty($data['id_personal_entrega']) || empty($data['id_personal_recepcion'])) {
                throw new Exception("Personal de entrega y recepción son obligatorios.");
            }

            $remito = new Remito(
                id: null,
                nroRemito: $data['nro_remito'] ?? 'REM-' . time(),
                tipo: 'Material',
                idCuadrillaOrigen: $data['id_cuadrilla_origen'] ?? null,
                idCuadrillaDestino: $data['id_cuadrilla_destino'] ?? null,
                idProveedor: $data['id_proveedor'] ?? null,
                idPersonalEntrega: $data['id_personal_entrega'],
                idPersonalRecepcion: $data['id_personal_recepcion'],
                destinoObra: $data['destino_obra'] ?? null,
                fechaEmision: date('Y-m-d H:i:s'),
                usuarioSistemaId: $data['usuario_sistema_id'],
                items: $data['items'] // elements with id_material and cantidad
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
                'details' => 'EntregasMaterialService::createRemitoMultiple'
            ];
        }
    }

    private function generateUuid()
    {
        return bin2hex(random_bytes(16));
    }
}
