<?php
require_once '../../../config/database.php';
require_once '../../../includes/header.php'; // For session check

if (!tienePermiso('compras')) {
    die('Acceso denegado');
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die('ID de orden no válido');
}

// Fetch Order
$stmt = $pdo->prepare("
    SELECT po.*, p.razon_social, p.cuit, p.direccion, p.telefono, p.email, p.nombre_contacto
    FROM compras_ordenes po 
    JOIN proveedores p ON po.id_proveedor = p.id_proveedor 
    WHERE po.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Orden no encontrada');
}

// Fetch Items
$stmtItems = $pdo->prepare("SELECT * FROM compras_items_orden WHERE id_orden = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Company Info (Placeholder or Config)
$companyName = "EMPRESA S.A.";
$companyAddress = "Dirección de la Empresa";
$companyPhone = "(000) 000-0000";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>OC
        <?= htmlspecialchars($order['nro_orden']) ?>
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info h1 {
            font-size: 24px;
            margin: 0 0 5px 0;
        }

        .po-info {
            text-align: right;
        }

        .po-info h2 {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #000;
        }

        .section-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 11px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #333;
            color: #fff;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .num {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            background: #f0f0f0;
            border-top: 2px solid #333;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 11px;
            color: #666;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }

            .section-box {
                background: #fff !important;
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #333; color: #fff; border: none; cursor: pointer;">🖨️ Imprimir /
            Guardar PDF</button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #ccc; border: none; cursor: pointer;">Cerrar</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="company-info">
                <h1>
                    <?= $companyName ?>
                </h1>
                <p>
                    <?= $companyAddress ?>
                </p>
                <p>Tel:
                    <?= $companyPhone ?>
                </p>
            </div>
            <div class="po-info">
                <h2>ORDEN DE COMPRA</h2>
                <p style="font-size: 16px;"><strong>
                        <?= htmlspecialchars($order['nro_orden']) ?>
                    </strong></p>
                <p>Fecha:
                    <?= date('d/m/Y', strtotime($order['fecha_creacion'])) ?>
                </p>
                <p>Estado:
                    <?= ucfirst($order['estado']) ?>
                </p>
            </div>
        </div>

        <div class="section-box">
            <div class="section-title">Proveedor</div>
            <p><strong>
                    <?= htmlspecialchars($order['razon_social']) ?>
                </strong></p>
            <?php if ($order['cuit']): ?>
                <p>CUIT:
                    <?= htmlspecialchars($order['cuit']) ?>
                </p>
            <?php endif; ?>
            <?php if ($order['direccion']): ?>
                <p>Dirección:
                    <?= htmlspecialchars($order['direccion']) ?>
                </p>
            <?php endif; ?>
            <?php if ($order['telefono']): ?>
                <p>Tel:
                    <?= htmlspecialchars($order['telefono']) ?>
                </p>
            <?php endif; ?>
            <?php if ($order['email']): ?>
                <p>Email:
                    <?= htmlspecialchars($order['email']) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($order['fecha_entrega_pactada']): ?>
            <div class="section-box" style="background: #fff; border: 1px solid #000;">
                <strong>FECHA DE ENTREGA SOLICITADA:</strong>
                <?= date('d/m/Y', strtotime($order['fecha_entrega_pactada'])) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="num" style="width: 80px;">Cant.</th>
                    <th class="num" style="width: 100px;">Precio Unit.</th>
                    <th class="num" style="width: 100px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item):
                    $subtotal = $item['cantidad'] * $item['precio_unitario'];
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['descripcion']) ?>
                        </td>
                        <td class="num">
                            <?= floatval($item['cantidad']) ?>
                        </td>
                        <td class="num">$
                            <?= number_format($item['precio_unitario'], 2) ?>
                        </td>
                        <td class="num">$
                            <?= number_format($subtotal, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">TOTAL:</td>
                    <td class="num">$
                        <?= number_format($order['monto_total'], 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Notas:</strong></p>
            <ul>
                <li>Favor de citar el número de Orden de Compra en su factura.</li>
                <li>Horario de recepción: Lunes a Viernes de 8:00 a 17:00 hs.</li>
            </ul>
            <br><br><br>
            <div style="display: flex; justify-content: space-between; margin-top: 40px;">
                <div style="border-top: 1px solid #333; width: 200px; padding-top: 5px; text-align: center;">Autorizado
                    Por</div>
                <div style="border-top: 1px solid #333; width: 200px; padding-top: 5px; text-align: center;">Recibido
                    Conforme</div>
            </div>
        </div>
    </div>
</body>

</html>