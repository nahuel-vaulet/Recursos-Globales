<?php
/**
 * [!] ARCH: Vista de impresión/PDF de ODTs con materiales
 * [→] Diseño minimalista y compacto para máximo aprovechamiento de hoja
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

$modePdf = isset($_GET['mode']) && $_GET['mode'] === 'pdf';

$ids = [];
if (!empty($_GET['ids'])) {
    $ids = array_map('intval', explode(',', $_GET['ids']));
} else {
    $rows = $pdo->query("SELECT id_odt FROM odt_maestro WHERE estado_gestion IN ('Finalizado', 'Aprobado por inspector', 'Ejecutado') ORDER BY nro_odt_assa")->fetchAll(PDO::FETCH_COLUMN);
    $ids = $rows;
}

if (empty($ids)) {
    echo '<p>No hay ODTs para imprimir.</p>';
    exit;
}

$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion, o.prioridad,
           t.nombre as tipo_trabajo, t.codigo_trabajo,
           c.nombre_cuadrilla, ps.fecha_programada
    FROM odt_maestro o
    LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
    LEFT JOIN (
        SELECT id_odt, id_cuadrilla, fecha_programada
        FROM programacion_semanal
        WHERE id_programacion IN (SELECT MAX(id_programacion) FROM programacion_semanal GROUP BY id_odt)
    ) ps ON o.id_odt = ps.id_odt
    LEFT JOIN cuadrillas c ON ps.id_cuadrilla = c.id_cuadrilla
    WHERE o.id_odt IN ($placeholders)
    ORDER BY o.nro_odt_assa
");
$stmt->execute($ids);
$odts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Materiales
$stmtMat = $pdo->prepare("
    SELECT om.id_odt, m.nombre, om.cantidad, m.unidad_medida
    FROM odt_materiales om
    JOIN maestro_materiales m ON m.id_material = om.id_material
    WHERE om.id_odt IN ($placeholders)
    ORDER BY om.id_odt, m.nombre
");
$stmtMat->execute($ids);
$matByOdt = [];
foreach ($stmtMat->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $matByOdt[$m['id_odt']][] = $m;
}

// Ítems
$stmtItems = $pdo->prepare("
    SELECT id_odt, descripcion_item, medida_1, medida_2, medida_3, unidad
    FROM odt_items
    WHERE id_odt IN ($placeholders) AND seleccionado = 1
    ORDER BY id_odt, id_item
");
$stmtItems->execute($ids);
$itemsByOdt = [];
foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $it) {
    $itemsByOdt[$it['id_odt']][] = $it;
}

$logoPath = '/APP-Prueba/assets/img/RG_Logo.png';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ODTs — Recursos Globales</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, system-ui, sans-serif;
            font-size: 8pt;
            color: #1a1a1a;
            background: #fff;
            padding: 15px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            font-family: 'Inter', sans-serif;
        }

        .toolbar button {
            padding: 8px 18px;
            font-size: 9pt;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity .15s;
        }

        .toolbar button:hover {
            opacity: .85;
        }

        .tb-print {
            background: #111;
            color: #fff;
        }

        .tb-pdf {
            background: #dc2626;
            color: #fff;
        }

        .tb-back {
            background: #f3f4f6;
            color: #374151;
        }

        /* ── HEADER ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 2px solid #111;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-logo {
            height: 32px;
        }

        .header-title {
            font-size: 11pt;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .header-sub {
            font-size: 7pt;
            color: #888;
            font-weight: 400;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .header-date {
            font-size: 7pt;
            color: #999;
            text-align: right;
        }

        /* ── ODT ROW — ultra compact ── */
        .odt {
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }

        .odt:last-child {
            border-bottom: none;
        }

        .odt-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 2px;
        }

        .odt-id {
            font-weight: 700;
            font-size: 8pt;
            color: #111;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .odt-addr {
            font-weight: 600;
            font-size: 8.5pt;
            color: #333;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .odt-status {
            font-size: 6.5pt;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .st-ejecutado {
            background: #dcfce7;
            color: #166534;
        }

        .st-aprobado {
            background: #e0f2fe;
            color: #075985;
        }

        .st-retrabajo {
            background: #fee2e2;
            color: #991b1b;
        }

        .st-programado {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .st-default {
            background: #f3f4f6;
            color: #6b7280;
        }

        .st-urgente {
            background: #fef2f2;
            color: #dc2626;
            font-weight: 700;
        }

        .odt-meta {
            font-size: 7pt;
            color: #9ca3af;
            display: flex;
            gap: 10px;
            margin-bottom: 2px;
        }

        /* ── ITEMS inline ── */
        .odt-items {
            font-size: 7pt;
            color: #555;
            margin: 1px 0;
            padding-left: 2px;
        }

        .odt-items span {
            margin-right: 8px;
        }

        /* ── MATERIALS compact ── */
        .mat-row {
            display: flex;
            gap: 3px;
            font-size: 7pt;
            color: #555;
            flex-wrap: wrap;
            padding-left: 2px;
        }

        .mat-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 6.5pt;
            white-space: nowrap;
        }

        .mat-chip b {
            font-weight: 600;
            color: #111;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 6.5pt;
            color: #ccc;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            .toolbar {
                display: none !important;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar no-print">
        <button class="tb-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="tb-pdf" onclick="window.print()">📄 Guardar PDF</button>
        <button class="tb-back" onclick="history.back()">← Volver</button>
    </div>

    <div class="header">
        <div class="header-left">
            <img src="<?php echo $logoPath; ?>" alt="RG" class="header-logo">
            <div>
                <div class="header-title">Reporte de ODTs</div>
                <div class="header-sub">Recursos Globales Business Company</div>
            </div>
        </div>
        <div class="header-date">
            <?php echo date('d/m/Y H:i'); ?><br>
            <?php echo count($odts); ?> registros
        </div>
    </div>

    <?php foreach ($odts as $o):
        $materiales = $matByOdt[$o['id_odt']] ?? [];
        $items = $itemsByOdt[$o['id_odt']] ?? [];

        $stMap = [
            'Ejecutado' => 'st-ejecutado',
            'Aprobado por inspector' => 'st-aprobado',
            'Retrabajo' => 'st-retrabajo',
            'Programado' => 'st-programado',
        ];
        $stClass = $stMap[$o['estado_gestion']] ?? 'st-default';
        ?>
        <div class="odt">
            <div class="odt-top">
                <span class="odt-id"><?php echo htmlspecialchars($o['nro_odt_assa']); ?></span>
                <span class="odt-addr"><?php echo htmlspecialchars($o['direccion'] ?: '—'); ?></span>
                <?php if ($o['prioridad'] === 'Urgente'): ?>
                    <span class="odt-status st-urgente">URGENTE</span>
                <?php endif; ?>
                <span class="odt-status <?php echo $stClass; ?>"><?php echo $o['estado_gestion']; ?></span>
            </div>

            <div class="odt-meta">
                <?php if ($o['tipo_trabajo']): ?>
                    <span><?php echo htmlspecialchars($o['tipo_trabajo']); ?></span>
                <?php endif; ?>
                <?php if ($o['nombre_cuadrilla']): ?>
                    <span>👷 <?php echo htmlspecialchars($o['nombre_cuadrilla']); ?></span>
                <?php endif; ?>
                <?php if ($o['fecha_programada']): ?>
                    <span><?php echo date('d/m/Y', strtotime($o['fecha_programada'])); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($items)): ?>
                <div class="odt-items">
                    <?php foreach ($items as $it): ?>
                        <span>• <?php echo htmlspecialchars($it['descripcion_item']); ?><?php
                           if ($it['medida_1'])
                               echo " {$it['medida_1']}";
                           if ($it['medida_2'])
                               echo "×{$it['medida_2']}";
                           if ($it['medida_3'])
                               echo "×{$it['medida_3']}";
                           if ($it['unidad'])
                               echo " {$it['unidad']}";
                           ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($materiales)): ?>
                <div class="mat-row">
                    <?php foreach ($materiales as $m): ?>
                        <span class="mat-chip">
                            <?php echo htmlspecialchars($m['nombre']); ?>
                            <b><?php echo $m['cantidad']; ?></b>
                            <?php echo htmlspecialchars($m['unidad_medida']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="footer">
        <span>Recursos Globales — Reporte generado automáticamente</span>
        <span><?php echo count($odts); ?> ODTs · <?php echo date('d/m/Y H:i'); ?></span>
    </div>

    <?php if ($modePdf): ?>
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
    <?php endif; ?>

</body>

</html>