<?php
require_once '../../../config/database.php';
session_start();

$id_despacho = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id_despacho) {
    die("ID de despacho no válido.");
}

// Fetch dispatch with related data (without id_cuadrilla dependency)
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        t.nombre AS tanque_nombre,
        t.tipo_combustible,
        v.marca AS vehiculo_marca,
        v.modelo AS vehiculo_modelo,
        v.patente AS vehiculo_patente
    FROM combustibles_despachos d
    LEFT JOIN combustibles_tanques t ON d.id_tanque = t.id_tanque
    LEFT JOIN vehiculos v ON d.id_vehiculo = v.id_vehiculo
    WHERE d.id_despacho = ?
");
$stmt->execute([$id_despacho]);
$despacho = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default cuadrilla name (column doesn't exist in despachos table)
$despacho['nombre_cuadrilla'] = $despacho['nombre_cuadrilla'] ?? 'No especificada';

if (!$despacho) {
    die("Despacho no encontrado.");
}

// Format date
$fecha_formateada = date('d/m/Y H:i', strtotime($despacho['fecha_hora']));

// Get dispatcher name
$despachante = $despacho['usuario_despacho'];
if (is_numeric($despachante)) {
    $stmtUser = $pdo->prepare("SELECT nombre_apellido FROM personal WHERE id_personal = ?");
    $stmtUser->execute([$despachante]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user)
        $despachante = $user['nombre_apellido'];
}

// Company info (could be fetched from settings)
$empresa = "Recursos Globales Business Company";
$empresa_direccion = "Oficina Central";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remito de Combustible #
        <?php echo $id_despacho; ?>
    </title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .remito-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left h1 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 5px;
        }

        .header-left p {
            font-size: 0.9em;
            color: #666;
        }

        .header-right {
            text-align: right;
        }

        .remito-number {
            font-size: 1.8em;
            font-weight: bold;
            color: #007bff;
        }

        .remito-type {
            background: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 0.85em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .info-item label {
            display: block;
            font-size: 0.75em;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-item span {
            font-size: 1.1em;
            font-weight: 500;
            color: #333;
        }

        .highlight-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }

        .highlight-box .amount {
            font-size: 3em;
            font-weight: bold;
        }

        .highlight-box .unit {
            font-size: 1.5em;
        }

        .highlight-box .fuel-type {
            font-size: 1em;
            opacity: 0.9;
            margin-top: 5px;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
            font-size: 0.9em;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
            font-size: 0.8em;
            color: #888;
            text-align: center;
        }

        .actions {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 25px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="actions no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir Remito
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='/APP-Prueba/modules/stock/index.php'">
            Volver al Dashboard
        </button>
    </div>

    <div class="remito-container">
        <div class="header">
            <div class="header-left">
                <h1>
                    <?php echo htmlspecialchars($empresa); ?>
                </h1>
                <p>
                    <?php echo htmlspecialchars($empresa_direccion); ?>
                </p>
            </div>
            <div class="header-right">
                <div class="remito-number">#
                    <?php echo str_pad($id_despacho, 6, '0', STR_PAD_LEFT); ?>
                </div>
                <div class="remito-type">REMITO DE COMBUSTIBLE</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Fecha y Hora</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Fecha de Emisión</label>
                    <span>
                        <?php echo $fecha_formateada; ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Tanque de Origen</label>
                    <span>
                        <?php echo htmlspecialchars($despacho['tanque_nombre'] ?? 'N/A'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="highlight-box">
            <div class="amount">
                <?php echo number_format($despacho['litros'], 2); ?>
            </div>
            <div class="unit">LITROS</div>
            <div class="fuel-type">
                <?php echo htmlspecialchars($despacho['tipo_combustible'] ?? 'Combustible'); ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Destinatario</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Cuadrilla</label>
                    <span>
                        <?php echo htmlspecialchars($despacho['nombre_cuadrilla'] ?? 'N/A'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Vehículo</label>
                    <span>
                        <?php
                        if ($despacho['vehiculo_marca']) {
                            echo htmlspecialchars($despacho['vehiculo_marca'] . ' ' . $despacho['vehiculo_modelo'] . ' (' . $despacho['vehiculo_patente'] . ')');
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Conductor / Responsable</label>
                    <span>
                        <?php echo htmlspecialchars($despacho['usuario_conductor'] ?? 'N/A'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Odómetro Registrado</label>
                    <span>
                        <?php echo number_format($despacho['odometro_actual'], 0); ?> km
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($despacho['destino_obra'])): ?>
            <div class="section">
                <div class="section-title">Destino / Obra</div>
                <div class="info-item" style="background: #fff3cd;">
                    <span>
                        <?php echo htmlspecialchars($despacho['destino_obra']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Entregó:</strong>
                    <?php echo htmlspecialchars($despachante); ?>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Recibió:</strong>
                    <?php echo htmlspecialchars($despacho['usuario_conductor'] ?? ''); ?>
                </div>
            </div>
        </div>

        <div class="footer">
            Documento generado automáticamente por el Sistema de Gestión de Combustibles.<br>
            Remito ID:
            <?php echo $id_despacho; ?> | Generado:
            <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>

</body>

</html>