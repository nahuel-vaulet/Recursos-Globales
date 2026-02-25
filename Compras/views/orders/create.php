<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$error = '';
$success = '';

// Get providers
$providers = $pdo->query("SELECT * FROM providers ORDER BY name")->fetchAll();

// Get recent orders for reference
$recentOrders = $pdo->query("
    SELECT po.*, p.name as provider_name 
    FROM purchase_orders po 
    JOIN providers p ON po.provider_id = p.id 
    ORDER BY po.created_at DESC 
    LIMIT 10
")->fetchAll();

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

            // Generate PO number
            $year = date('Y');
            $lastPO = $pdo->query("SELECT po_number FROM purchase_orders WHERE po_number LIKE 'OC-$year-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
            if ($lastPO) {
                $lastNum = intval(substr($lastPO, -4));
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
                INSERT INTO purchase_orders (po_number, provider_id, status, total_amount, delivery_date_committed)
                VALUES (?, ?, 'emitida', ?, ?)
            ");
            $stmt->execute([$poNumber, $providerId, $total, $deliveryDate ?: null]);
            $poId = $pdo->lastInsertId();

            // Insert items
            $stmtItem = $pdo->prepare("INSERT INTO po_items (po_id, item_description, quantity, price_unit) VALUES (?, ?, ?, ?)");
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
            // Redirect to list with PDF popup
            echo "<script>window.open('pdf.php?id=$poId', '_blank'); window.location='list.php?created=$poNumber';</script>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al crear la OC: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Orden de Compra - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .items-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: end;
        }

        .btn-remove {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-md);
            cursor: pointer;
        }

        .btn-add {
            background: var(--success-color);
            color: white;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
        }

        .total-display {
            text-align: right;
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-body);
            border-radius: var(--radius-md);
        }

        .recent-orders {
            margin-bottom: 2rem;
        }

        .recent-orders h2 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .mini-table th,
        .mini-table td {
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        .mini-table th {
            background: var(--bg-body);
            font-weight: 500;
            color: var(--text-muted);
        }

        .mini-table tr:hover {
            background: var(--bg-body);
        }

        .history-section {
            margin-bottom: 2rem;
            display: none;
        }

        .history-section.active {
            display: block;
        }

        .history-section h2 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
        }

        .order-history-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .order-history-header {
            background: var(--bg-body);
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .order-history-items {
            padding: 0.5rem;
        }

        .order-history-items table {
            width: 100%;
            font-size: 0.8rem;
            border-collapse: collapse;
        }

        .order-history-items td {
            padding: 0.4rem 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .order-history-items tr:last-child td {
            border-bottom: none;
        }

        .price-cell {
            text-align: right;
            font-family: monospace;
        }

        .no-history {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .item-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="../dashboard/index.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Dashboard</a>
                <a href="../requests/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Solicitudes</a>
                <a href="../providers/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Proveedores</a>
                <a href="list.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Órdenes de Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤
                    <?= htmlspecialchars($userName) ?>
                </p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <h1 style="margin-bottom: 1.5rem;">Nueva Orden de Compra</h1>

            <!-- Historial de ODCs del proveedor seleccionado (se carga dinámicamente) -->
            <div id="provider-history" class="history-section card">
                <h2>📋 Historial de ODCs con este proveedor</h2>
                <div id="history-content">
                    <div class="no-history">Selecciona un proveedor para ver el historial</div>
                </div>
            </div>


            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="card" id="po-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Proveedor *</label>
                        <select name="provider_id" id="provider-select" class="form-control" required onchange="loadProviderHistory()">
                            <option value="">Seleccione un proveedor</option>
                            <?php foreach ($providers as $prov): ?>
                                <option value="<?= $prov['id'] ?>">
                                    <?= htmlspecialchars($prov['name']) ?> (
                                    <?= ucfirst($prov['contract_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de Entrega Comprometida</label>
                        <input type="date" name="delivery_date" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="items-section">
                    <h3 style="margin-bottom: 1rem;">Ítems de la Orden</h3>
                    <div id="items-container">
                        <div class="item-row">
                            <div class="form-group">
                                <label class="form-label">Descripción *</label>
                                <input type="text" name="items[0][description]" class="form-control item-desc" required
                                    placeholder="Ej: Cemento Portland 50kg">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="items[0][quantity]" class="form-control item-qty" value="1"
                                    min="0.01" step="0.01" onchange="updateTotal()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unidad</label>
                                <input type="text" name="items[0][unit]" class="form-control" value="unidades"
                                    placeholder="kg, m, unidades">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Precio Unit.</label>
                                <input type="number" name="items[0][price]" class="form-control item-price" value="0"
                                    min="0" step="0.01" onchange="updateTotal()">
                            </div>
                            <button type="button" class="btn-remove" onclick="removeItem(this)"
                                title="Eliminar">✕</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-add" onclick="addItem()" style="margin-top: 0.5rem;">+ Agregar
                        ítem</button>

                    <div class="total-display">
                        Total: $<span id="total-amount">0.00</span>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Crear Orden de Compra</button>
                    <a href="list.php" class="btn" style="background: var(--bg-body);">Cancelar</a>
                </div>
            </form>
        </main>
    </div>

    <script>
        let itemIndex = 1;

        function addItem() {
            const container = document.getElementById('items-container');
            const html = `
                <div class="item-row">
                    <div class="form-group">
                        <input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" placeholder="Descripción del ítem">
                    </div>
                    <div class="form-group">
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" value="1" min="0.01" step="0.01" onchange="updateTotal()">
                    </div>
                    <div class="form-group">
                        <input type="text" name="items[${itemIndex}][unit]" class="form-control" value="unidades">
                    </div>
                    <div class="form-group">
                        <input type="number" name="items[${itemIndex}][price]" class="form-control item-price" value="0" min="0" step="0.01" onchange="updateTotal()">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">✕</button>
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
                historySection.classList.remove('active');
                return;
            }

            historySection.classList.add('active');
            historyContent.innerHTML = '<div class="no-history">Cargando historial...</div>';

            fetch(`get_provider_history.php?provider_id=${providerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.orders.length === 0) {
                        historyContent.innerHTML = '<div class="no-history">No hay órdenes anteriores con este proveedor</div>';
                        return;
                    }

                    let html = '';
                    data.orders.forEach(order => {
                        html += `
                            <div class="order-history-card">
                                <div class="order-history-header">
                                    <div>
                                        <strong>${order.po_number}</strong>
                                        <span style="margin-left: 0.5rem; color: var(--text-muted);">${order.created_at}</span>
                                    </div>
                                    <span style="color: var(--success-color);">$${parseFloat(order.total_amount).toFixed(2)}</span>
                                </div>
                                <div class="order-history-items">
                                    <table>
                        `;
                        order.items.forEach(item => {
                            html += `
                                <tr>
                                    <td style="width: 50%;">${item.item_description}</td>
                                    <td style="width: 20%;" class="price-cell">${parseFloat(item.quantity).toFixed(2)} u</td>
                                    <td style="width: 30%;" class="price-cell"><strong>$${parseFloat(item.price_unit).toFixed(2)}</strong>/u</td>
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
                    historyContent.innerHTML = '<div class="no-history">Error al cargar historial</div>';
                });
        }
    </script>
</body>

</html>