<?php
require_once '../../../config/database.php';
require_once '../../../includes/header.php';

if (!tienePermiso('compras')) {
    header('Location: ../../../index.php?msg=forbidden');
    exit;
}

$error = '';
$success = '';

// Get providers
$providers = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerId = intval($_POST['provider_id'] ?? 0);
    $deliveryDate = $_POST['delivery_date'] ?? null;
    $items = $_POST['items'] ?? [];

    if (!$providerId) {
        $error = 'Debe seleccionar un proveedor';
    } elseif (empty($items) || empty($items[0]['description'])) {
        $error = 'Debe agregar al menos un ítem';
    } else {
        try {
            $pdo->beginTransaction();

            // Generate PO number (OC-YYYY-XXXX)
            $year = date('Y');
            $lastPO = $pdo->query("SELECT nro_orden FROM compras_ordenes WHERE nro_orden LIKE 'OC-$year-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
            if ($lastPO) {
                // Extract last 4 digits
                $parts = explode('-', $lastPO);
                $lastNum = intval(end($parts));
                $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNum = '0001';
            }
            $poNumber = "OC-$year-$newNum";

            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                if (!empty($item['description'])) {
                    $total += floatval($item['quantity'] ?? 1) * floatval($item['price'] ?? 0);
                }
            }

            // Insert PO
            $stmt = $pdo->prepare("
                INSERT INTO compras_ordenes (nro_orden, id_proveedor, estado, monto_total, fecha_entrega_pactada, fecha_creacion)
                VALUES (?, ?, 'emitida', ?, ?, NOW())
            ");
            $stmt->execute([$poNumber, $providerId, $total, $deliveryDate ?: null]);
            $poId = $pdo->lastInsertId();

            // Insert items
            $stmtItem = $pdo->prepare("INSERT INTO compras_items_orden (id_orden, descripcion, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                if (!empty($item['description'])) {
                    $stmtItem->execute([
                        $poId,
                        $item['description'],
                        floatval($item['quantity'] ?? 1),
                        floatval($item['price'] ?? 0)
                    ]);
                }
            }

            $pdo->commit();
            // Redirect to list with PDF (via JS in index or simple redirect)
            // We'll redirect to index with a flag to open PDF
            echo "<script>
                window.open('print.php?id=$poId', '_blank');
                window.location = 'index.php?msg=created&new_id=$poId';
            </script>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al crear la OC: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid" style="padding: 0 20px;">

    <div style="margin-bottom: 25px;">
        <h1 style="margin: 0;"><i class="fas fa-file-contract"></i> Nueva Orden de Compra</h1>
        <p style="margin: 5px 0 0; color: #666;">Emitir una nueva orden a proveedor</p>
    </div>

    <!-- Provider History Panel -->
    <div id="provider-history" class="card"
        style="display: none; margin-bottom: 25px; border-left: 4px solid var(--color-info);">
        <h3 style="font-size: 1.1em; margin-bottom: 15px; color: var(--color-primary);">📋 Historial reciente con este
            proveedor</h3>
        <div id="history-content" style="max-height: 300px; overflow-y: auto;">
            <!-- Loaded via AJAX -->
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"
            style="background-color: #FEE2E2; color: #B91C1C; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card" style="padding: 25px;">
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Proveedor
                    *</label>
                <select name="provider_id" id="provider-select" class="form-control" required
                    onchange="loadProviderHistory()"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Seleccione un proveedor</option>
                    <?php foreach ($providers as $prov): ?>
                        <option value="<?= $prov['id_proveedor'] ?>">
                            <?= htmlspecialchars($prov['razon_social']) ?>
                            <?= $prov['cuit'] ? '(' . htmlspecialchars($prov['cuit']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Fecha de Entrega
                    Pactada</label>
                <input type="date" name="delivery_date" class="form-control" min="<?= date('Y-m-d') ?>"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>

        <div class="items-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h3 style="margin-bottom: 15px; font-size: 1.1em; color: var(--color-primary);">Ítems de la Orden</h3>
            <div id="items-container">
                <div class="item-row"
                    style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 10px; margin-bottom: 15px; align-items: end;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.9em; margin-bottom: 5px;">Descripción *</label>
                        <input type="text" name="items[0][description]" class="form-control item-desc" required
                            placeholder="Ej: Material X"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.9em; margin-bottom: 5px;">Cantidad</label>
                        <input type="number" name="items[0][quantity]" class="form-control item-qty" value="1"
                            min="0.01" step="0.01" onchange="updateTotal()"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.9em; margin-bottom: 5px;">Precio Unit.</label>
                        <input type="number" name="items[0][price]" class="form-control item-price" value="0" min="0"
                            step="0.01" onchange="updateTotal()"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 5px;">&nbsp;</label>
                        <button type="button" class="btn-custom-danger" onclick="removeItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-custom" onclick="addItem()">
                <i class="fas fa-plus"></i> Agregar ítem
            </button>

            <div class="total-display"
                style="text-align: right; margin-top: 20px; font-size: 1.5em; font-weight: bold; color: var(--color-primary);">
                Total: $<span id="total-amount">0.00</span>
            </div>
        </div>

        <style>
            .btn-custom {
                border-radius: 8px;
                padding: 10px 20px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-weight: 500;
                transition: all 0.2s ease;
                text-decoration: none !important;
                background: transparent;
                border: 1px solid var(--color-primary);
                color: var(--color-primary);
                cursor: pointer;
            }

            .btn-custom:hover {
                background: rgba(100, 181, 246, 0.1);
                transform: translateY(-1px);
                color: var(--color-primary);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .btn-custom-danger {
                border-radius: 8px;
                padding: 8px 12px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                background: transparent;
                border: 1px solid var(--color-danger);
                color: var(--color-danger);
                cursor: pointer;
            }

            .btn-custom-danger:hover {
                background: rgba(239, 68, 68, 0.1);
                transform: translateY(-1px);
                color: var(--color-danger);
            }
        </style>

        <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
            <a href="index.php" class="btn-custom">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn-custom">
                <i class="fas fa-file-contract"></i> Emitir Orden
            </button>
        </div>
    </form>
</div>

<script>
    let itemIndex = 1;

    function addItem() {
        const container = document.getElementById('items-container');
        const html = `
            <div class="item-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 10px; margin-bottom: 15px; align-items: end;">
                <div class="form-group">
                    <input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" placeholder="Descripción"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" value="1" min="0.01" step="0.01" onchange="updateTotal()"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <input type="number" name="items[${itemIndex}][price]" class="form-control item-price" value="0" min="0" step="0.01" onchange="updateTotal()"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 5px;">&nbsp;</label>
                    <button type="button" class="btn-custom-danger" onclick="removeItem(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        itemIndex++;
    }

    function removeItem(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('.item-row').remove();
            updateTotal();
        } else {
            alert('Debe haber al menos un ítem');
        }
    }

    function updateTotal() {
        let total = 0;
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
            total += qty * price;
        });
        document.getElementById('total-amount').textContent = total.toFixed(2);
    }

    function loadProviderHistory() {
        const select = document.getElementById('provider-select');
        const providerId = select.value;
        const historySection = document.getElementById('provider-history');
        const historyContent = document.getElementById('history-content');

        if (!providerId) {
            historySection.style.display = 'none';
            return;
        }

        historySection.style.display = 'block';
        historyContent.innerHTML = '<p style="color: #666; font-style: italic;">Cargando historial...</p>';

        fetch(`../api/get_provider_history.php?provider_id=${providerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.orders.length === 0) {
                    historyContent.innerHTML = '<p style="color: #666;">No hay órdenes anteriores con este proveedor.</p>';
                    return;
                }

                let html = '';
                data.orders.forEach(order => {
                    html += `
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 10px; overflow: hidden;">
                            <div style="background: var(--bg-secondary); padding: 8px 12px; display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color);">
                                <div style="color: var(--text-primary);"><strong>${order.nro_orden}</strong> <span style="color: var(--text-secondary); font-size: 0.9em;">(${order.formatted_date})</span></div>
                                <div style="color: var(--color-success); font-weight: bold;">$${parseFloat(order.monto_total).toFixed(2)}</div>
                            </div>
                            <div style="padding: 8px 12px;">
                                <table style="width: 100%; font-size: 0.9em; border-collapse: collapse; color: var(--text-primary);">
                    `;
                    order.items.forEach(item => {
                        html += `
                            <tr>
                                <td style="padding: 2px 0; border-bottom: 1px solid var(--border-color);">${item.descripcion}</td>
                                <td style="padding: 2px 0; text-align: right; width: 20%; border-bottom: 1px solid var(--border-color);">${parseFloat(item.cantidad)} u</td>
                                <td style="padding: 2px 0; text-align: right; width: 20%; border-bottom: 1px solid var(--border-color);"><strong>$${parseFloat(item.precio_unitario).toFixed(2)}</strong></td>
                            </tr>
                        `;
                    });
                    html += `
                                </table>
                            </div>
                        </div>
                    `;
                });
                historyContent.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                historyContent.innerHTML = '<p style="color: #DC2626;">Error al cargar historial.</p>';
            });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>