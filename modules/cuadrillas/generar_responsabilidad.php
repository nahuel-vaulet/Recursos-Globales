<?php
/**
 * Cuadrillas - Generar PDF de Responsabilidad de Herramientas
 * [✓] AUDITORÍA: Documento imprimible con detalle de herramientas, precios,
 *     fecha de asignación, disclaimer legal y firmas de integrantes.
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$id_cuadrilla = $_GET['id'] ?? null;
if (!$id_cuadrilla)
    die("Cuadrilla no especificada");

// Fetch Cuadrilla
$stmt = $pdo->prepare("SELECT c.*, v.patente, v.marca as v_marca, v.modelo as v_modelo 
                        FROM cuadrillas c 
                        LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id_vehiculo
                        WHERE c.id_cuadrilla = ?");
$stmt->execute([$id_cuadrilla]);
$cuadrilla = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuadrilla)
    die("Cuadrilla no encontrada");

// Fetch Members
$stmtMembers = $pdo->prepare("SELECT * FROM personal WHERE id_cuadrilla = ? ORDER BY nombre_apellido");
$stmtMembers->execute([$id_cuadrilla]);
$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// Fetch Tools
$stmtTools = $pdo->prepare("SELECT * FROM herramientas WHERE id_cuadrilla_asignada = ? ORDER BY nombre ASC");
$stmtTools->execute([$id_cuadrilla]);
$tools = $stmtTools->fetchAll(PDO::FETCH_ASSOC);

$totalTools = 0;
foreach ($tools as $t)
    $totalTools += $t['precio_reposicion'];

// Find leader
$lider = null;
foreach ($members as $m) {
    if (stripos($m['rol'], 'Jefe') !== false || stripos($m['rol'], 'Lider') !== false || stripos($m['rol'], 'Líder') !== false) {
        $lider = $m;
        break;
    }
}

// Vehicle info
$vehiculoStr = 'Sin Vehículo Asignado';
if (!empty($cuadrilla['patente'])) {
    $vehiculoStr = $cuadrilla['patente'] . ' - ' . $cuadrilla['v_marca'] . ' ' . $cuadrilla['v_modelo'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Responsabilidad de Herramientas - <?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a2e;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            font-size: 10pt;
            line-height: 1.5;
            background: white;
        }

        /* Header */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #0073A8;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .doc-header .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .doc-header .logo {
            max-width: 120px;
            max-height: 60px;
        }

        .doc-header .title-area {
            text-align: right;
        }

        .doc-header h1 {
            font-size: 16pt;
            color: #0073A8;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }

        .doc-header .subtitle {
            font-size: 12pt;
            color: #333;
            font-weight: 500;
        }

        .doc-header .date-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 10pt;
            margin-top: 6px;
        }

        /* Info boxes */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-box .label {
            font-size: 8pt;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-box .value {
            font-size: 11pt;
            font-weight: 600;
            color: #1a1a2e;
        }

        /* Section titles */
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #0073A8;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Members list */
        .members-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }

        .member-item {
            background: #f0f9ff;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #0073A8;
            font-size: 9.5pt;
        }

        .member-item .name {
            font-weight: 600;
        }

        .member-item .detail {
            color: #6c757d;
            font-size: 8.5pt;
        }

        /* Tools table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }

        thead th {
            background: #0073A8;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        thead th:first-child {
            border-radius: 6px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 6px 0 0;
        }

        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e9ecef;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #e8f4fd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: 700;
            background: #e8f4fd !important;
            font-size: 10pt;
        }

        .total-row td {
            border-top: 2px solid #0073A8;
            padding: 10px;
        }

        /* Legal disclaimer */
        .legal-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-left: 4px solid #e2a400;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 9pt;
            text-align: justify;
            line-height: 1.6;
        }

        .legal-box strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
            font-size: 9.5pt;
        }

        /* Signatures */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px 30px;
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #333;
        }

        .sig-box .sig-name {
            font-weight: 700;
            font-size: 10pt;
            margin-bottom: 2px;
        }

        .sig-box .sig-role {
            color: #6c757d;
            font-size: 8.5pt;
        }

        .sig-box .sig-dni {
            font-size: 8.5pt;
            color: #333;
        }

        /* Print controls */
        .no-print {
            margin-bottom: 15px;
        }

        .btn-print {
            background: #0073A8;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 10pt;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #005a85;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 10pt;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        /* Footer */
        .doc-footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
            font-size: 8pt;
            color: #adb5bd;
            text-align: center;
        }

        @media print {
            body {
                padding: 10mm;
            }

            .no-print {
                display: none !important;
            }

            .legal-box {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tbody tr:nth-child(even) {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .total-row {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <!-- Print Button -->
    <div class="no-print" style="display: flex; justify-content: flex-end; gap: 10px;">
        <button onclick="window.print()" class="btn-print">
            🖨️ Imprimir / Guardar PDF
        </button>
        <a href="form.php?id=<?php echo $id_cuadrilla; ?>" class="btn-back">
            ← Volver al Formulario
        </a>
    </div>

    <!-- Header -->
    <div class="doc-header">
        <div class="logo-area">
            <img src="../../assets/img/logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div>
                <h1>ACTA DE RESPONSABILIDAD</h1>
                <div class="subtitle">Herramientas y Equipos de Trabajo</div>
            </div>
        </div>
        <div class="title-area">
            <div class="date-badge">
                📅 Fecha de Entrega: <?php echo date('d/m/Y'); ?>
            </div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-box">
            <div class="label">Cuadrilla</div>
            <div class="value"><?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?></div>
        </div>
        <div class="info-box">
            <div class="label">Responsable / Jefe de Cuadrilla</div>
            <div class="value"><?php echo $lider ? htmlspecialchars($lider['nombre_apellido']) : 'Sin Jefe Asignado'; ?>
            </div>
        </div>
        <div class="info-box">
            <div class="label">Zona Asignada</div>
            <div class="value"><?php echo htmlspecialchars($cuadrilla['zona_asignada'] ?: 'Sin zona'); ?></div>
        </div>
        <div class="info-box">
            <div class="label">Vehículo</div>
            <div class="value"><?php echo htmlspecialchars($vehiculoStr); ?></div>
        </div>
    </div>

    <!-- Members Section -->
    <div class="section-title">👥 Integrantes de la Cuadrilla (<?php echo count($members); ?>)</div>
    <?php if (!empty($members)): ?>
        <div class="members-list">
            <?php foreach ($members as $m): ?>
                <div class="member-item">
                    <div class="name"><?php echo htmlspecialchars($m['nombre_apellido']); ?></div>
                    <div class="detail"><?php echo htmlspecialchars($m['rol']); ?> — DNI:
                        <?php echo htmlspecialchars($m['dni'] ?? 'S/D'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #999; font-style: italic; margin-bottom: 15px;">Sin integrantes registrados</p>
    <?php endif; ?>

    <!-- Tools Table -->
    <div class="section-title">🔧 Detalle de Herramientas y Equipos Asignados</div>
    <table>
        <thead>
            <tr>
                <th style="width: 35px;" class="text-center">N°</th>
                <th>Herramienta</th>
                <th>Marca / Modelo</th>
                <th>Nro Serie</th>
                <th class="text-center">Fecha Asignación</th>
                <th class="text-right">Valor Reposición</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tools)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #999; padding: 20px;">No hay herramientas asignadas
                    </td>
                </tr>
            <?php else: ?>
                <?php $idx = 0;
                foreach ($tools as $t):
                    $idx++; ?>
                    <tr>
                        <td class="text-center" style="font-weight: 600; color: #6c757d;"><?php echo $idx; ?></td>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($t['nombre']); ?></td>
                        <td><?php echo htmlspecialchars(($t['marca'] ?? '') . ' ' . ($t['modelo'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($t['numero_serie'] ?? '-'); ?></td>
                        <td class="text-center">
                            <?php
                            if (!empty($t['fecha_asignacion'])) {
                                $fecha = new DateTime($t['fecha_asignacion']);
                                echo $fecha->format('d/m/Y');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="text-right" style="font-weight: 600;">$
                            <?php echo number_format($t['precio_reposicion'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL VALORIZADO</td>
                    <td class="text-right">$ <?php echo number_format($totalTools, 2); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Legal Disclaimer -->
    <div class="legal-box">
        <strong>⚠️ DECLARACIÓN DE RESPONSABILIDAD Y COMPROMISO DE CUIDADO:</strong>
        Por medio de la presente, los abajo firmantes, integrantes de la cuadrilla
        <strong style="display:inline;">"<?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?>"</strong>,
        declaran haber recibido en conformidad las herramientas y equipos detallados en este documento,
        en perfecto estado de uso y funcionamiento, en la fecha indicada.<br><br>

        Los integrantes asumen la <strong style="display:inline;">responsabilidad total</strong> por el cuidado,
        custodia y uso adecuado de dichos elementos durante el desempeño de sus tareas laborales.<br><br>

        <strong style="display:inline; color: #c62828;">En caso de pérdida, extravío o rotura ocasionada por
            negligencia o mal uso comprobado, la empresa se reserva el derecho de descontar el
            VALOR DE REPOSICIÓN indicado en este documento de los haberes correspondientes del o los responsables,
            conforme a la normativa laboral vigente y los procedimientos internos de la compañía.</strong><br><br>

        Asimismo, se deja constancia de que las herramientas entregadas son de uso exclusivo para las tareas asignadas
        y no podrán ser prestadas, cedidas o utilizadas con fines personales.
    </div>

    <!-- Signatures -->
    <div class="signatures-grid">
        <?php if ($members): ?>
            <?php foreach ($members as $m): ?>
                <div class="sig-box">
                    <div class="sig-name"><?php echo htmlspecialchars($m['nombre_apellido']); ?></div>
                    <div class="sig-role"><?php echo htmlspecialchars($m['rol']); ?></div>
                    <div class="sig-dni">DNI: <?php echo htmlspecialchars($m['dni'] ?? 'S/D'); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="sig-box">
                Firma Responsable
            </div>
        <?php endif; ?>

        <!-- Always include Supervisor signature -->
        <div class="sig-box">
            <div class="sig-name">Supervisor / Entrega</div>
            <div class="sig-role">Responsable de la entrega</div>
            <div class="sig-dni">Firma y Aclaración</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        Documento generado el <?php echo date('d/m/Y H:i'); ?> — Recursos Globales Business Company — Uso interno
    </div>

</body>

</html>