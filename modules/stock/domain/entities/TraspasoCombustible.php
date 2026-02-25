<?php
namespace Stock\Domain\Entities;

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
        public float $precioUnitario = 0.0,
        public float $importeTotal = 0.0,
        public string $estadoVerificacion = 'Verifica',
        public int $esAlerta = 0,
        public ?string $observacionesAlerta = null
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

    public function getDesvioPorcentaje(): float
    {
        if ($this->litrosEstimados <= 0)
            return 0.0;
        return ($this->getDiferenciaCarga() / $this->litrosEstimados) * 100;
    }
}
