<?php
namespace Stock\Infrastructure\Repositories;

use PDO;
use Stock\Domain\Entities\Remito;

class PDOStockRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
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
            $remito->destino_obra ?? null,
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

            // If it's a material delivery from office to squad
            if ($remito->tipo === 'Material' && !empty($item['id_material'])) {
                $this->registrarMovimientoMaterial($remito, $item);
            }
        }

        return $idRemito;
    }

    private function registrarMovimientoMaterial(Remito $remito, array $item): void
    {
        // 1. Record movement in legacy 'movimientos' table for backward compatibility
        $stmt = $this->pdo->prepare("
            INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho)
            VALUES (?, 'Entrega_Oficina_Cuadrilla', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $remito->nroRemito,
            $item['id_material'],
            $item['cantidad'],
            $remito->idCuadrillaDestino,
            $remito->usuarioSistemaId
        ]);

        // 2. Deduct from office stock (stock_saldos)
        $stmtStock = $this->pdo->prepare("
            UPDATE stock_saldos 
            SET stock_oficina = stock_oficina - ?, updated_at = NOW()
            WHERE id_material = ?
        ");
        $stmtStock->execute([$item['cantidad'], $item['id_material']]);

        // 3. Add to squad stock (stock_cuadrilla) — UPSERT
        if ($remito->idCuadrillaDestino) {
            $stmtCheck = $this->pdo->prepare("
                SELECT COUNT(*) FROM stock_cuadrilla WHERE id_cuadrilla = ? AND id_material = ?
            ");
            $stmtCheck->execute([$remito->idCuadrillaDestino, $item['id_material']]);
            $exists = (int) $stmtCheck->fetchColumn();

            if ($exists > 0) {
                $stmtUp = $this->pdo->prepare("
                    UPDATE stock_cuadrilla SET cantidad = cantidad + ? WHERE id_cuadrilla = ? AND id_material = ?
                ");
                $stmtUp->execute([$item['cantidad'], $remito->idCuadrillaDestino, $item['id_material']]);
            } else {
                $stmtIns = $this->pdo->prepare("
                    INSERT INTO stock_cuadrilla (id_cuadrilla, id_material, cantidad) VALUES (?, ?, ?)
                ");
                $stmtIns->execute([$remito->idCuadrillaDestino, $item['id_material'], $item['cantidad']]);
            }
        }
    }

    public function getUltimoKm(int $idVehiculo): array
    {
        $stmt = $this->pdo->prepare("SELECT km_actual, consumo_promedio FROM vehiculos WHERE id_vehiculo = ?");
        $stmt->execute([$idVehiculo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['km_actual' => 0, 'consumo_promedio' => 15.0];
    }

    public function saveFuelTransfer(int $idRemito, array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO spot_traspasos_combustible (id_remito, id_tanque, id_vehiculo, km_ultimo, km_actual, 
                                                   litros_estimados, litros_cargados, precio_unitario, importe_total, 
                                                   estado_verificacion, es_alerta, observaciones_alerta)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $idRemito,
            $data['id_tanque'],
            $data['id_vehiculo'],
            $data['km_ultimo'],
            $data['km_actual'],
            $data['litros_estimados'] ?? 0,
            $data['litros_cargados'],
            $data['precio_unitario'] ?? 0,
            $data['importe_total'] ?? 0,
            $data['estado_verificacion'] ?? 'Verifica',
            $data['es_alerta'] ?? 0,
            $data['observaciones_alerta'] ?? null
        ]);

        // Update tank stock
        $stmtTank = $this->pdo->prepare("UPDATE combustibles_tanques SET stock_actual = stock_actual - ? WHERE id_tanque = ?");
        $stmtTank->execute([$data['litros_cargados'], $data['id_tanque']]);

        // Update vehicle odometer persistence
        $stmtVeh = $this->pdo->prepare("UPDATE vehiculos SET km_actual = ? WHERE id_vehiculo = ?");
        $stmtVeh->execute([$data['km_actual'], $data['id_vehiculo']]);

        // --- NEW: INSERT INTO LEGACY combustibles_despachos FOR DASHBOARD STATS ---
        // Resolve Driver Name for legacy visibility (Dashboard uses it)
        $driverName = '';
        if (!empty($data['id_personal_recepcion'])) {
            $stmtDrv = $this->pdo->prepare("SELECT nombre_apellido FROM personal WHERE id_personal = ?");
            $stmtDrv->execute([$data['id_personal_recepcion']]);
            $driverName = (string) $stmtDrv->fetchColumn();
        }

        $stmtLegacy = $this->pdo->prepare("
        INSERT INTO combustibles_despachos (id_tanque, id_vehiculo, id_cuadrilla, fecha_hora, litros, odometro_actual, usuario_despacho, usuario_conductor, destino_obra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
        $stmtLegacy->execute([
            $data['id_tanque'],
            $data['id_vehiculo'],
            $data['id_cuadrilla_recepcion'] ?? null,
            $data['fecha_hora'] ?? date('Y-m-d H:i:s'),
            $data['litros_cargados'],
            $data['km_actual'],
            $data['usuario_sistema_id'] ?? 1,
            $driverName,
            $data['destino_obra'] ?? ''
        ]);

        // --- NEW: UPDATE SQUAD STOCK AND MOVEMENT LOG ---
        if (!empty($data['id_cuadrilla_recepcion'])) {
            $idCuadrilla = (int) $data['id_cuadrilla_recepcion'];
            $litros = (float) $data['litros_cargados'];

            // Dynamically resolve Material ID based on Tank type to avoid hardcoded 999 failures
            // Tanque con 'Nafta' -> Material 1000, Diesel/Otros -> Material 1001
            $stmtTankType = $this->pdo->prepare("SELECT tipo_combustible FROM combustibles_tanques WHERE id_tanque = ?");
            $stmtTankType->execute([$data['id_tanque']]);
            $tankType = (string) $stmtTankType->fetchColumn();

            $idMaterial = (strpos(strtolower($tankType), 'nafta') !== false) ? 1000 : 1001;

            // 1. Record movement in legacy 'movimientos' table
            // [!] FIX: Use valid Enum 'Entrega_Oficina_Cuadrilla'
            try {
                $stmtMov = $this->pdo->prepare("
                    INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho, fecha_hora)
                    VALUES (?, 'Entrega_Oficina_Cuadrilla', ?, ?, ?, ?, NOW())
                ");
                $stmtMov->execute([
                    $data['nro_remito'] ?? ('REM-COMB-' . time()),
                    $idMaterial,
                    $litros,
                    $idCuadrilla,
                    $data['usuario_sistema_id'] ?? 1
                ]);
            } catch (\Exception $e) {
                // If it fails with FK, we try a final fallback to id_material 999 if it's different
                if ($idMaterial !== 999) {
                    $stmtMov->execute([
                        $data['nro_remito'] ?? ('REM-COMB-' . time()),
                        999,
                        $litros,
                        $idCuadrilla,
                        $data['usuario_sistema_id'] ?? 1
                    ]);
                } else {
                    throw new \Exception("Error registrando movimiento (FK Material): " . $e->getMessage() . " [ID_MAT: $idMaterial, ID_CUAD: $idCuadrilla]");
                }
            }

            // 2. Add to squad stock (stock_cuadrilla)
            $stmtCheck = $this->pdo->prepare("
                SELECT COUNT(*) FROM stock_cuadrilla WHERE id_cuadrilla = ? AND id_material = ?
            ");
            $stmtCheck->execute([$idCuadrilla, $idMaterial]);
            if ((int) $stmtCheck->fetchColumn() > 0) {
                $stmtUp = $this->pdo->prepare("
                    UPDATE stock_cuadrilla SET cantidad = cantidad + ? WHERE id_cuadrilla = ? AND id_material = ?
                ");
                $stmtUp->execute([$litros, $idCuadrilla, $idMaterial]);
            } else {
                $stmtIns = $this->pdo->prepare("
                    INSERT INTO stock_cuadrilla (id_cuadrilla, id_material, cantidad) VALUES (?, ?, ?)
                ");
                $stmtIns->execute([$idCuadrilla, $idMaterial, $litros]);
            }
        }
    }

    public function replenishTank(int $idTanque, float $cantidad, int $usuarioId): void
    {
        // 1. Update Tank Balance
        $stmt = $this->pdo->prepare("UPDATE combustibles_tanques SET stock_actual = stock_actual + ? WHERE id_tanque = ?");
        $stmt->execute([$cantidad, $idTanque]);

        // 2. Record movement in 'movimientos' table (Abastecimiento/Replenishment)
        // Dynamically resolve Material ID by Tank type (same as saveFuelTransfer)
        $stmtTankType = $this->pdo->prepare("SELECT tipo_combustible FROM combustibles_tanques WHERE id_tanque = ?");
        $stmtTankType->execute([$idTanque]);
        $tankType = (string) $stmtTankType->fetchColumn();
        $idMaterial = (strpos(strtolower($tankType), 'nafta') !== false) ? 1000 : 1001;

        $stmtMov = $this->pdo->prepare("
            INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho, fecha_hora)
            VALUES (?, 'Abastecer_Tanque', ?, ?, NULL, ?, NOW())
        ");
        $stmtMov->execute([
            'REPL-' . date('Ymd-His'),
            $idMaterial,
            $cantidad,
            $usuarioId
        ]);
    }
}
