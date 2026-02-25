<?php
/**
 * [!] ARCH: Exportador de ODTs a Excel (.xlsx)
 * [✓] AUDIT: Recibe columnas seleccionadas via GET, genera archivo con PhpSpreadsheet
 * [→] EDITAR: Agregar columnas adicionales si se amplía el modelo
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../services/ODTService.php';
require_once '../../services/DateUtil.php';

verificarSesion();

if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ─── AVAILABLE COLUMNS (key => label) ───────────────────
$availableColumns = [
    'nro_odt_assa' => 'Nº ODT',
    'direccion' => 'Dirección',
    'tipo_trabajo' => 'Tipo de Trabajo',
    'codigo_trabajo' => 'Código Trabajo',
    'estado_gestion' => 'Estado',
    'prioridad' => 'Prioridad',
    'orden' => 'Orden',
    'nombre_cuadrilla' => 'Cuadrilla',
    'fecha_asignacion' => 'Fecha Asignación',
    'fecha_vencimiento' => 'Fecha Vencimiento',
    'urgente_flag' => 'Urgente',
    'fecha_creacion' => 'Fecha Creación',
    'observaciones' => 'Observaciones',
];

// ─── PARSE SELECTED COLUMNS ────────────────────────────
$selectedKeys = isset($_GET['cols']) ? explode(',', $_GET['cols']) : array_keys($availableColumns);

// Filtrar solo columnas válidas
$exportColumns = [];
foreach ($selectedKeys as $key) {
    $key = trim($key);
    if (isset($availableColumns[$key])) {
        $exportColumns[$key] = $availableColumns[$key];
    }
}

if (empty($exportColumns)) {
    die('No se seleccionaron columnas válidas para exportar.');
}

// ─── FETCH DATA ─────────────────────────────────────────
$rolActual = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '';
$idCuadrillaUsuario = $_SESSION['usuario_id_cuadrilla'] ?? null;

$odtService = new ODTService($pdo);
$odts = $odtService->listarConFiltros([], $rolActual, $idCuadrillaUsuario ? (int) $idCuadrillaUsuario : null);

// ─── APPLY PER-COLUMN FILTERS FROM GET (f_*) ────────────
$odts = array_filter($odts, function ($o) {
    // Text contains filters
    $textFilters = ['nro_odt_assa', 'direccion', 'tipo_trabajo', 'codigo_trabajo', 'orden', 'observaciones'];
    foreach ($textFilters as $col) {
        $val = $_GET["f_{$col}"] ?? '';
        if ($val !== '' && stripos($o[$col] ?? '', $val) === false) {
            return false;
        }
    }

    // Estado exact match
    if (!empty($_GET['f_estado_gestion']) && ($o['estado_gestion'] ?? '') !== $_GET['f_estado_gestion']) {
        return false;
    }

    // Prioridad exact match
    if (isset($_GET['f_prioridad']) && $_GET['f_prioridad'] !== '' && (int) ($o['prioridad'] ?? 0) !== (int) $_GET['f_prioridad']) {
        return false;
    }

    // Cuadrilla exact match
    if (!empty($_GET['f_nombre_cuadrilla']) && ($o['nombre_cuadrilla'] ?? '') !== $_GET['f_nombre_cuadrilla']) {
        return false;
    }

    // Urgente filter
    if (isset($_GET['f_urgente_flag']) && $_GET['f_urgente_flag'] !== '' && (int) ($o['urgente_flag'] ?? 0) !== (int) $_GET['f_urgente_flag']) {
        return false;
    }

    // Date range filters (desde/hasta for date columns)
    $dateColumns = ['fecha_asignacion', 'fecha_vencimiento', 'fecha_creacion'];
    foreach ($dateColumns as $dateCol) {
        $desde = $_GET["f_{$dateCol}_desde"] ?? '';
        $hasta = $_GET["f_{$dateCol}_hasta"] ?? '';
        $dateVal = $o[$dateCol] ?? '';
        if ($desde && $dateVal && $dateVal < $desde)
            return false;
        if ($hasta && $dateVal && $dateVal > $hasta)
            return false;
    }

    return true;
});
$odts = array_values($odts); // Re-index

// ─── BUILD SPREADSHEET ──────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('ODTs');

// Header row
$colIdx = 1;
foreach ($exportColumns as $key => $label) {
    $sheet->setCellValue([$colIdx, 1], $label);
    $colIdx++;
}

// Style header
$headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($exportColumns)) . '1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1B263B'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '415A77'],
        ],
    ],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

// Data rows
$rowIdx = 2;
foreach ($odts as $o) {
    $colIdx = 1;
    foreach ($exportColumns as $key => $label) {
        $value = '';
        switch ($key) {
            case 'nro_odt_assa':
                $value = $o['nro_odt_assa'] ?? '';
                break;
            case 'direccion':
                $value = $o['direccion'] ?? '';
                break;
            case 'tipo_trabajo':
                $value = $o['tipo_trabajo'] ?? '';
                break;
            case 'codigo_trabajo':
                $value = $o['codigo_trabajo'] ?? '';
                break;
            case 'estado_gestion':
                $value = $o['estado_gestion'] ?? '';
                break;
            case 'prioridad':
                $pri = (int) ($o['prioridad'] ?? 0);
                $labels = [0 => 'Normal', 1 => 'Media', 2 => 'Alta', 3 => 'Crítica'];
                $value = $labels[$pri] ?? 'Normal';
                break;
            case 'orden':
                $value = $o['orden'] ?? '';
                break;
            case 'nombre_cuadrilla':
                $value = $o['nombre_cuadrilla'] ?? 'Sin asignar';
                break;
            case 'fecha_asignacion':
                $value = $o['fecha_asignacion'] ? DateUtil::formatear($o['fecha_asignacion']) : '';
                break;
            case 'fecha_vencimiento':
                $value = $o['fecha_vencimiento'] ? DateUtil::formatear($o['fecha_vencimiento']) : '';
                break;
            case 'urgente_flag':
                $value = $o['urgente_flag'] ? 'Sí' : 'No';
                break;
            case 'fecha_creacion':
                $value = !empty($o['fecha_creacion']) ? DateUtil::formatear($o['fecha_creacion']) : '';
                break;
            case 'observaciones':
                $value = $o['observaciones'] ?? '';
                break;
        }
        $sheet->setCellValue([$colIdx, $rowIdx], $value);
        $colIdx++;
    }

    // Zebra stripe
    if ($rowIdx % 2 === 0) {
        $range = 'A' . $rowIdx . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($exportColumns)) . $rowIdx;
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F0F4F8');
    }

    $rowIdx++;
}

// Auto-size columns
for ($i = 1; $i <= count($exportColumns); $i++) {
    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
}

// Auto-filter
$sheet->setAutoFilter($headerRange);

// Freeze header
$sheet->freezePane('A2');

// ─── OUTPUT ─────────────────────────────────────────────
$filename = 'ODTs_Export_' . date('Y-m-d_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
