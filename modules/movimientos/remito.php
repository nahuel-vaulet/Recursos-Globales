<?php
require_once '../../config/database.php';

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../stock/index.php?msg=error");
    exit;
}

$id_remito = intval($_GET['id']);

// Fetch Remito Header
$sql = "SELECT r.*, c.nombre_cuadrilla 
        FROM remitos r 
        LEFT JOIN cuadrillas c ON r.id_cuadrilla = c.id_cuadrilla 
        WHERE r.id_remito = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_remito]);
$remito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$remito) {
    header("Location: ../stock/index.php?msg=error&details=Remito+no+encontrado");
    exit;
}

// Fetch Remito Details
$sqlDet = "SELECT rd.*, m.nombre as material, m.codigo, m.unidad_medida 
           FROM remitos_detalle rd 
           JOIN maestro_materiales m ON rd.id_material = m.id_material 
           WHERE rd.id_remito = ?";
$stmtDet = $pdo->prepare($sqlDet);
$stmtDet->execute([$id_remito]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remito
        <?php echo htmlspecialchars($remito['numero_remito']); ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #0077b6;
            --color-dark: #1a1a2e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }

        .remito-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .remito-header {
            background: linear-gradient(135deg, var(--color-dark) 0%, #16213e 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .company-info p {
            opacity: 0.8;
            font-size: 0.9em;
        }

        .remito-number {
            text-align: right;
        }

        .remito-number .number {
            font-size: 1.5em;
            font-weight: bold;
            color: #00d4ff;
            letter-spacing: 1px;
        }

        .remito-number .date {
            opacity: 0.8;
            margin-top: 5px;
        }

        .remito-body {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--color-primary);
        }

        .info-box label {
            display: block;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-box .value {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }

        .materials-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .materials-table thead {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .materials-table th {
            padding: 12px 15px;
            text-align: left;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #495057;
        }

        .materials-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .materials-table tr:hover {
            background: #f8f9fa;
        }

        .materials-table .qty {
            font-family: 'Consolas', monospace;
            font-size: 1.2em;
            font-weight: bold;
            color: var(--color-primary);
        }

        .materials-table .unit {
            color: #888;
            font-size: 0.85em;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px dashed #dee2e6;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            height: 60px;
            margin-bottom: 10px;
        }

        .signature-label {
            font-size: 0.9em;
            color: #666;
        }

        .actions {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
        }

        .btn-primary:hover {
            background: #005b8f;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: white;
            border: 2px solid #dee2e6;
            color: #333;
        }

        .btn-outline:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #d4edda;
            color: #155724;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .remito-container {
                box-shadow: none;
                border-radius: 0;
            }

            .remito-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="remito-container">
        <div class="remito-header">
            <div class="company-info">
                <h1><i class="fas fa-building"></i> Recursos Globales S.A.</h1>
                <p>Sistema de Gestión de Stock</p>
            </div>
            <div class="remito-number">
                <div class="number">
                    <?php echo htmlspecialchars($remito['numero_remito']); ?>
                </div>
                <div class="date">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('d/m/Y H:i', strtotime($remito['fecha_emision'])); ?>
                </div>
            </div>
        </div>

        <div class="remito-body">
            <div class="info-grid">
                <div class="info-box">
                    <label>Origen</label>
                    <div class="value"><i class="fas fa-building"></i> Oficina Central</div>
                </div>
                <div class="info-box">
                    <label>Destino</label>
                    <div class="value"><i class="fas fa-hard-hat"></i>
                        <?php echo htmlspecialchars($remito['nombre_cuadrilla']); ?>
                    </div>
                </div>
            </div>

            <table class="materials-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 55%;">Material</th>
                        <th style="width: 15%; text-align: right;">Cantidad</th>
                        <th style="width: 15%;">Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($item['codigo'] ?: '-'); ?></code></td>
                            <td><strong>
                                    <?php echo htmlspecialchars($item['material']); ?>
                                </strong></td>
                            <td style="text-align: right;">
                                <span class="qty">
                                    <?php echo number_format($item['cantidad'], 2); ?>
                                </span>
                            </td>
                            <td><span class="unit">
                                    <?php echo htmlspecialchars($item['unidad_medida']); ?>
                                </span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($remito['observaciones'])): ?>
                <div class="info-box" style="margin-bottom: 20px;">
                    <label>Observaciones</label>
                    <div class="value">
                        <?php echo htmlspecialchars($remito['observaciones']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>Entregó</strong><br>
                        <?php echo htmlspecialchars($remito['usuario_emision'] ?: 'Almacén'); ?>
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>Recibió</strong><br>
                        Responsable de Cuadrilla
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="../stock/index.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Volver al Stock
            </a>
            <div>
                <span class="success-badge">
                    <i class="fas fa-check-circle"></i> Transferencia completada
                </span>
                <button onclick="window.print()" class="btn btn-primary" style="margin-left: 10px;">
                    <i class="fas fa-print"></i> Imprimir Remito
                </button>
            </div>
        </div>
    </div>
</body>

</html>