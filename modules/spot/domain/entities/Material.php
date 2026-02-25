<?php
namespace Spot\Domain\Entities;

class Material
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public string $unidadMedida,
        public float $stockActual = 0.0
    ) {
    }
}
