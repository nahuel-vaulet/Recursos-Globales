<?php
namespace Spot\Domain\Entities;

class Remito
{
    public function __construct(
        public ?int $id,
        public string $nroRemito,
        public string $tipo, // 'Material' | 'Combustible'
        public ?int $idCuadrillaOrigen,
        public ?int $idCuadrillaDestino,
        public ?int $idProveedor,
        public int $idPersonalEntrega,
        public int $idPersonalRecepcion,
        public ?string $destinoObra,
        public ?string $fechaEmision,
        public int $usuarioSistemaId,
        public array $items = []
    ) {
    }
}
