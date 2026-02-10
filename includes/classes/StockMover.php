<?php
// includes/classes/StockMover.php

class StockMover
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Process a batch of movements from the form
     * @return array|bool Returns array with id_remito if remito was generated, true otherwise
     */
    public function processBatch($postData)
    {
        if (!isset($postData['id_material']) || empty($postData['id_material'])) {
            throw new Exception("No hay materiales seleccionados.");
        }

        // Extract Header Data
        $tipo = $postData['tipo_movimiento'];
        $fecha = $postData['fecha_movimiento'];
        $nro_documento = $postData['nro_documento'];
        $id_cuadrilla = !empty($postData['id_cuadrilla']) ? $postData['id_cuadrilla'] : null;
        $id_proveedor = !empty($postData['id_proveedor']) ? $postData['id_proveedor'] : null;
        $usu_despacho = $postData['usuario_despacho'];
        $usu_recepcion = $postData['usuario_recepcion']; // Depending on type, this might be 'Cuadrilla Responsibility'

        // Arrays
        $materials = $postData['id_material'];
        $quantities = $postData['cantidad'];

        // Validate Header Context
        if ($tipo === 'Entrega_Oficina_Cuadrilla' && !$id_cuadrilla) {
            throw new Exception("Debe seleccionar una Cuadrilla para realizar una Entrega.");
        }
        if ($tipo === 'Consumo_Cuadrilla_Obra' && !$id_cuadrilla) {
            throw new Exception("Debe seleccionar la Cuadrilla que consume el material.");
        }

        try {
            $this->pdo->beginTransaction();

            $fecha_full = $fecha . ' ' . date('H:i:s'); // Append current time for sorting
            $movementIds = []; // Store movement IDs for remito
            $processedItems = []; // Store material/quantity pairs for remito

            for ($i = 0; $i < count($materials); $i++) {
                $id_mat = $materials[$i];
                $qty = floatval($quantities[$i]);

                if (empty($id_mat) || $qty <= 0)
                    continue;

                // 1. Record Movement History
                $movId = $this->recordMovement($tipo, $id_mat, $qty, $id_cuadrilla, $id_proveedor, $nro_documento, $usu_despacho, $usu_recepcion, $fecha_full);
                $movementIds[] = $movId;
                $processedItems[] = ['id_material' => $id_mat, 'cantidad' => $qty, 'id_movimiento' => $movId];

                // 2. Update Stock Balances (ACID)
                $this->updateBalances($tipo, $id_mat, $qty, $id_cuadrilla);
            }

            // 3. Generate Remito for Entrega_Oficina_Cuadrilla
            $result = true;
            if ($tipo === 'Entrega_Oficina_Cuadrilla' && !empty($processedItems)) {
                $id_remito = $this->generateRemito($id_cuadrilla, $processedItems, $usu_despacho);
                $result = ['id_remito' => $id_remito];
            }

            $this->pdo->commit();
            return $result;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e; // Re-throw to be caught by controller
        }
    }

    private function recordMovement($tipo, $id_mat, $qty, $id_cuadrilla, $id_proveedor, $doc, $usu_d, $usu_r, $fecha)
    {
        $sql = "INSERT INTO movimientos (
                    tipo_movimiento, id_material, cantidad, id_cuadrilla, id_proveedor, 
                    nro_documento, usuario_despacho, usuario_recepcion, fecha_hora
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $tipo,
            $id_mat,
            $qty,
            $id_cuadrilla,
            $id_proveedor,
            $doc,
            $usu_d,
            $usu_r,
            $fecha
        ]);

        return $this->pdo->lastInsertId();
    }

    private function updateBalances($tipo, $id_mat, $qty, $id_cuadrilla)
    {
        switch ($tipo) {
            case 'Compra_Material':
            case 'Recepcion_ASSA_Oficina':
                // Increase Office Stock
                $this->adjustStock('stock_saldos', 'stock_oficina', $id_mat, $qty, 'add');
                break;

            case 'Entrega_Oficina_Cuadrilla':
                // Decrease Office, Increase Squad
                // 1. Check Office Availability
                $current = $this->getStock('stock_saldos', 'stock_oficina', 'id_material', $id_mat);
                if ($current < $qty) {
                    throw new Exception("Stock de Oficina insuficiente para Material ID $id_mat. Disponible: $current");
                }

                $this->adjustStock('stock_saldos', 'stock_oficina', $id_mat, $qty, 'subtract');
                $this->adjustSquadStock($id_cuadrilla, $id_mat, $qty, 'add');
                break;

            case 'Consumo_Cuadrilla_Obra':
                // Decrease Squad Stock
                // 1. Check Squad Availability
                $current = $this->getSquadStock($id_cuadrilla, $id_mat);
                if ($current < $qty) {
                    throw new Exception("Stock de Cuadrilla insuficiente para Material ID $id_mat. Disponible: $current");
                }

                $this->adjustSquadStock($id_cuadrilla, $id_mat, $qty, 'subtract');
                break;

            case 'Devolucion_ASSA':
            case 'Devolucion_Compra':
                // Decrease Office Stock (material sale de oficina)
                $current = $this->getStock('stock_saldos', 'stock_oficina', 'id_material', $id_mat);
                if ($current < $qty) {
                    throw new Exception("Stock de Oficina insuficiente para Material ID $id_mat. Disponible: $current");
                }
                $this->adjustStock('stock_saldos', 'stock_oficina', $id_mat, $qty, 'subtract');
                break;

            default:
                throw new Exception("Tipo de movimiento no reconocido: $tipo");
        }
    }

    // --- Helpers ---

    private function getStock($table, $col, $pkCol, $pkVal)
    {
        $stmt = $this->pdo->prepare("SELECT $col FROM $table WHERE $pkCol = ?");
        $stmt->execute([$pkVal]);
        return $stmt->fetchColumn() ?: 0;
    }

    private function getSquadStock($id_cuadrilla, $id_material)
    {
        $stmt = $this->pdo->prepare("SELECT cantidad FROM stock_cuadrilla WHERE id_cuadrilla = ? AND id_material = ?");
        $stmt->execute([$id_cuadrilla, $id_material]);
        return $stmt->fetchColumn() ?: 0;
    }

    private function adjustStock($table, $col, $id_mat, $qty, $op)
    {
        // Upsert logic for simple tables not implemented perfectly here as 'stock_saldos' assumes rows exist or we insert on fly.
        // For simplicity, we assume row might not exist for 'add', but must exist for 'subtract'.

        $exists = $this->getStock($table, 'count(*)', 'id_material', $id_mat); // Cheap check? No, select count.
        // Actually getStock returns 0 if not found, but we can't subtract 0.

        // Lets use simple logic: Try Update, if 0 rows affect -> Insert (only for add)

        $operator = ($op === 'add') ? '+' : '-';
        $sql = "UPDATE $table SET $col = $col $operator ? WHERE id_material = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$qty, $id_mat]);

        if ($stmt->rowCount() == 0 && $op === 'add') {
            // Row didn't exist, insert it
            $sqlInsert = "INSERT INTO $table (id_material, $col) VALUES (?, ?)";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([$id_mat, $qty]);
        }
    }

    private function adjustSquadStock($id_cuadrilla, $id_mat, $qty, $op)
    {
        $operator = ($op === 'add') ? '+' : '-';
        $sql = "UPDATE stock_cuadrilla SET cantidad = cantidad $operator ? WHERE id_cuadrilla = ? AND id_material = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$qty, $id_cuadrilla, $id_mat]);

        if ($stmt->rowCount() == 0 && $op === 'add') {
            $ins = $this->pdo->prepare("INSERT INTO stock_cuadrilla (id_cuadrilla, id_material, cantidad) VALUES (?, ?, ?)");
            $ins->execute([$id_cuadrilla, $id_mat, $qty]);
        }
    }

    /**
     * Generate a Remito for material delivery to a squad
     * @param int $id_cuadrilla Target squad ID
     * @param array $items Array of ['id_material' => int, 'cantidad' => float, 'id_movimiento' => int]
     * @param string $usuario User who issued the remito
     * @return int ID of the generated remito
     */
    private function generateRemito($id_cuadrilla, $items, $usuario)
    {
        // Generate unique remito number: REM-YYYYMMDD-XXXX
        $datePrefix = date('Ymd');

        // Get next sequence for today
        $stmt = $this->pdo->prepare("SELECT COUNT(*) + 1 as next_num FROM remitos WHERE numero_remito LIKE ?");
        $stmt->execute(["REM-{$datePrefix}-%"]);
        $nextNum = $stmt->fetchColumn();

        $numero_remito = sprintf("REM-%s-%04d", $datePrefix, $nextNum);

        // Insert remito header
        $sqlHeader = "INSERT INTO remitos (numero_remito, id_cuadrilla, tipo_remito, usuario_emision) 
                      VALUES (?, ?, 'Entrega_Cuadrilla', ?)";
        $stmtHeader = $this->pdo->prepare($sqlHeader);
        $stmtHeader->execute([$numero_remito, $id_cuadrilla, $usuario]);

        $id_remito = $this->pdo->lastInsertId();

        // Insert remito details
        $sqlDetail = "INSERT INTO remitos_detalle (id_remito, id_material, cantidad, id_movimiento) VALUES (?, ?, ?, ?)";
        $stmtDetail = $this->pdo->prepare($sqlDetail);

        foreach ($items as $item) {
            $stmtDetail->execute([
                $id_remito,
                $item['id_material'],
                $item['cantidad'],
                $item['id_movimiento']
            ]);
        }

        return $id_remito;
    }
}
