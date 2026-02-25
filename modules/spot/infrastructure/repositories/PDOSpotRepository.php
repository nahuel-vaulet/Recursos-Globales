<?php
namespace Spot\Infrastructure\Repositories;

use PDO;
use Spot\Domain\Entities\Remito;

class PDOSpotRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function saveRemito(Remito $remito): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO spot_remitos (nro_remito, tipo, id_cuadrilla_origen, id_cuadrilla_destino, 
                                     id_proveedor, id_personal_entrega, id_personal_recepcion, 
                                     destino_obra, usuario_sistema_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $remito->nroRemito,
            $remito->tipo,
            $remito->idCuadrillaOrigen,
            $remito->idCuadrillaDestino,
            $remito->idProveedor,
            $remito->idPersonalEntrega,
            $remito->idPersonalRecepcion,
            $remito->destinoObra,
            $remito->usuarioSistemaId
        ]);

        $idRemito = (int) $this->pdo->lastInsertId();

        foreach ($remito->items as $item) {
            $stmtItem = $this->pdo->prepare("
                INSERT INTO spot_remito_items (id_remito, id_material, id_tanque, cantidad)
                VALUES (?, ?, ?, ?)
            ");
            $stmtItem->execute([
                $idRemito,
                $item['id_material'] ?? null,
                $item['id_tanque'] ?? null,
                $item['cantidad']
            ]);
        }

        return $idRemito;
    }

    public function getUltimoKm(int $idVehiculo): int
    {
        $stmt = $this->pdo->prepare("SELECT km_actual FROM vehiculos WHERE id_vehiculo = ?");
        $stmt->execute([$idVehiculo]);
        return (int) $stmt->fetchColumn() ?: 0;
    }
}
