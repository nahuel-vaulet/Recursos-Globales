<?php
require_once '../../config/db.php';

// Get order ID
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    die('ID de orden no válido');
}

// Get order data
$stmt = $pdo->prepare("
    SELECT po.*, p.name as provider_name, p.contact_name, p.contact_email, p.tax_id
    FROM purchase_orders po 
    JOIN providers p ON po.provider_id = p.id 
    WHERE po.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Orden no encontrada');
}

// Get items
$stmtItems = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Calculate total
$total = 0;
foreach ($items as $item) {
    $total += $item['quantity'] * $item['price_unit'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>OC
        <?= htmlspecialchars($order['po_number']) ?>
    </title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #0F172A;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-info h1 {
            font-size: 20px;
            color: #0F172A;
        }

        .company-info p {
            color: #666;
            font-size: 11px;
        }

        .po-info {
            text-align: right;
        }

        .po-info h2 {
            font-size: 24px;
            color: #0F172A;
            margin-bottom: 5px;
        }

        .po-info .date {
            color: #666;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background: #0F172A;
            color: white;
            padding: 5px 10px;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .provider-box {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
        }

        .provider-box strong {
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: #0F172A;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .items-table .number {
            text-align: right;
        }

        .total-row {
            background: #0F172A !important;
            color: white;
            font-weight: bold;
        }

        .total-row td {
            border: none;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
        }

        .delivery-info {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            padding: 10px;
            margin-bottom: 20px;
        }

        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #0F172A;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>

    <div class="header">
        <div class="company-info">
            <h1>🏢 EMPRESA S.A.</h1>
            <p>Módulo de Compras</p>
            <p>Dirección de la empresa</p>
            <p>Tel: (000) 000-0000</p>
        </div>
        <div class="po-info">
            <h2>
                <?= htmlspecialchars($order['po_number']) ?>
            </h2>
            <p class="date">Emitida:
                <?= date('d/m/Y', strtotime($order['created_at'])) ?>
            </p>
            <p>Estado: <strong>
                    <?= ucfirst($order['status']) ?>
                </strong></p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Proveedor</div>
        <div class="provider-box">
            <strong>
                <?= htmlspecialchars($order['provider_name']) ?>
            </strong>
            <?php if ($order['tax_id']): ?>
                <p>RUT/NIF:
                    <?= htmlspecialchars($order['tax_id']) ?>
                </p>
            <?php endif; ?>
            <?php if ($order['contact_name']): ?>
                <p>Contacto:
                    <?= htmlspecialchars($order['contact_name']) ?>
                </p>
            <?php endif; ?>
            <?php if ($order['contact_email']): ?>
                <p>Email:
                    <?= htmlspecialchars($order['contact_email']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($order['delivery_date_committed']): ?>
        <div class="delivery-info">
            <strong>📅 Fecha de Entrega Comprometida:</strong>
            <?= date('d/m/Y', strtotime($order['delivery_date_committed'])) ?>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Detalle de la Orden</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Descripción</th>
                    <th style="width: 80px;" class="number">Cantidad</th>
                    <th style="width: 100px;" class="number">Precio Unit.</th>
                    <th style="width: 100px;" class="number">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= $i++ ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($item['item_description']) ?>
                        </td>
                        <td class="number">
                            <?= number_format($item['quantity'], 2) ?>
                        </td>
                        <td class="number">$
                            <?= number_format($item['price_unit'], 2) ?>
                        </td>
                        <td class="number">$
                            <?= number_format($item['quantity'] * $item['price_unit'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL:</td>
                    <td class="number">$
                        <?= number_format($total, 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><strong>Condiciones:</strong></p>
        <ul style="margin-left: 20px; margin-top: 5px;">
            <li>Los precios incluyen/excluyen IVA según acuerdo</li>
            <li>Entrega en dirección acordada</li>
            <li>Pago según términos del contrato</li>
        </ul>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">Emitido por (Comprador)</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Recibido por (Proveedor)</div>
            </div>
        </div>
    </div>
</body>

</html>