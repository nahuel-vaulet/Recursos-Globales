<?php
namespace Spot\Domain\Entities;

class TraspasoCombustible
{
    public function __construct(
        public ?int $id,
        public int $idRemito,
        public int $idTanque,
        public int $idVehiculo,
        public int $kmUltimo,
        public int $kmActual,
        public float $litrosEstimados,
        public float $litrosCargados,
        public string $estadoVerificacion = 'Verifica'
    ) {
    }

    public function getKmDiferencia(): int
    {
        return $this->kmActual - $this->kmUltimo;
    }

    public function getDiferenciaCarga(): float
    {
        return $this->litrosCargados - $this->litrosEstimados;
    }
}
