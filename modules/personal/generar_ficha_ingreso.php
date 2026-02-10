<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

$id = $_GET['id'] ?? null;
if (!$id)
    die("ID no especificado.");

$stmt = $pdo->prepare("
    SELECT p.*, c.nombre_cuadrilla 
    FROM personal p
    LEFT JOIN cuadrillas c ON p.id_cuadrilla = c.id_cuadrilla
    WHERE p.id_personal = ?
");
$stmt->execute([$id]);
$personal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$personal)
    die("Personal no encontrado.");

function v($val)
{
    return !empty($val) ? htmlspecialchars($val) : '<span style="color:#d1d5db">---</span>';
}
function d($val)
{
    return !empty($val) ? date('d/m/Y', strtotime($val)) : '<span style="color:#d1d5db">--/--/--</span>';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Legajo-<?php echo htmlspecialchars($personal['nombre_apellido']); ?></title>
    <style>
        :root {
            --primary: #0073A8;
            --dark: #0f172a;
            --gray: #64748b;
            --border: #cbd5e1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 9px;
            /* Base font reduced */
            color: var(--dark);
            background: #fff;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10px;
            /* Minimal padding */
            overflow: hidden;
            /* Force clip */
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            button {
                display: none;
            }

            @page {
                margin: 0.3cm;
                size: A4;
            }

            /* Tight print margins */
        }

        /* HEADER - Horizontal Layout */
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 5px;
            margin-bottom: 8px;
            height: 110px;
            /* Increased to fit larger logo */
        }

        .logo-box {
            width: 200px;
            /* Increased width for larger logo */
        }

        .logo-box img {
            max-height: 100px;
            max-width: 100%;
            object-fit: contain;
            /* Doubled size */
        }

        /* Smaller logo */

        .title-box {
            flex: 1;
            text-align: center;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 8px;
            color: var(--gray);
            font-weight: 600;
            letter-spacing: 2px;
        }

        .meta-box {
            width: 120px;
            text-align: right;
            font-size: 8px;
            color: var(--gray);
            border-left: 1px solid var(--border);
            padding-left: 8px;
            line-height: 1.4;
        }

        .meta-box strong {
            color: var(--dark);
            font-size: 10px;
            display: block;
        }

        /* PROFILE COMPACT */
        .profile-wrap {
            display: flex;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            height: 90px;
        }

        .photo {
            width: 72px;
            height: 72px;
            /* Passport size */
            background: #fff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-data {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            align-content: start;
        }

        .name-row {
            grid-column: span 4;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 2px;
        }

        .name-row h1 {
            font-size: 14px;
            margin: 0;
            color: var(--dark);
            display: inline-block;
            margin-right: 10px;
        }

        .name-row .role {
            font-size: 10px;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
        }

        .p-item {
            display: flex;
            flex-direction: column;
        }

        .p-lbl {
            font-size: 7px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 700;
        }

        .p-val {
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* SECTIONS */
        .sec-title {
            background: #f1f5f9;
            color: var(--dark);
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 6px;
            border-left: 3px solid var(--primary);
            margin: 8px 0 4px 0;
        }

        /* 6 COLUMN GRID FOR DATA */
        .grid-6 {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px 8px;
        }

        .span-2 {
            grid-column: span 2;
        }

        .span-3 {
            grid-column: span 3;
        }

        .span-4 {
            grid-column: span 4;
        }

        .span-6 {
            grid-column: span 6;
        }

        .f-group {
            border-bottom: 1px dotted #e2e8f0;
        }

        .f-lbl {
            display: block;
            font-size: 7px;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 0px;
        }

        .f-val {
            display: block;
            font-size: 9px;
            color: var(--dark);
            font-weight: 600;
            min-height: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .f-val.multiline {
            white-space: normal;
            line-height: 1.1;
        }

        /* TABLES COMPACT */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 8px;
        }

        th {
            background: #f8fafc;
            text-align: center;
            padding: 2px;
            border: 1px solid var(--border);
            color: var(--gray);
            font-weight: 700;
        }

        td {
            padding: 3px;
            border: 1px solid #e2e8f0;
            color: var(--dark);
            text-align: center;
        }

        /* LEGAL & SIGNATURES */
        .legal {
            margin-top: 10px;
            font-size: 7.5px;
            /* Tiny but legal */
            text-align: justify;
            line-height: 1.25;
            border: 1px solid var(--border);
            padding: 6px;
            background: #fff;
        }

        .legal b {
            color: var(--dark);
        }

        .sigs {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding: 0 50px;
        }

        .sig-box {
            width: 180px;
            border-top: 1px solid var(--dark);
            text-align: center;
            padding-top: 4px;
        }

        .sig-txt {
            font-weight: 700;
            font-size: 9px;
            display: block;
        }

        .sig-sub {
            font-size: 7px;
            color: var(--gray);
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <div class="no-print" style="margin-bottom: 10px; text-align: right;">
        <button onclick="window.print()"
            style="background:#0f172a; color:#fff; border:none; padding:6px 12px; border-radius:3px; cursor:pointer; font-size:10px; font-weight:bold;">IMPRIMIR
            (A4)</button>
    </div>

    <!-- HEADER: Compact Horizontal -->
    <header class="header">
        <div class="logo-box">
            <img src="../../assets/img/RG_Logo.png" alt="Logo">
        </div>
        <div class="title-box">
            <h2 class="doc-title">Ficha Técnica de Personal</h2>
            <div class="doc-subtitle">RECURSOS GLOBALES S.A.</div>
        </div>
        <div class="meta-box">
            LEGAJO: <strong>#<?php echo str_pad($personal['id_personal'], 4, '0', STR_PAD_LEFT); ?></strong>
            ALTA: <?php echo d($personal['fecha_ingreso']); ?>
        </div>
    </header>

    <!-- PROFILE: Compact Grid -->
    <div class="profile-wrap">
        <div class="photo">
            <?php if (!empty($personal['foto_usuario'])): ?>
                <img src="../../uploads/personal/fotos/<?php echo $personal['foto_usuario']; ?>">
            <?php else: ?>
                <div style="font-size:8px; color:#ccc; text-align:center;">FOTO</div>
            <?php endif; ?>
        </div>
        <div class="profile-data">
            <div class="name-row">
                <h1><?php echo v($personal['nombre_apellido']); ?></h1>
                <span class="role"><?php echo v($personal['rol']); ?> |
                    <?php echo v($personal['nombre_cuadrilla']); ?></span>
            </div>

            <div class="p-item"><span class="p-lbl">DNI</span><span
                    class="p-val"><?php echo v($personal['dni']); ?></span></div>
            <div class="p-item"><span class="p-lbl">CUIL</span><span
                    class="p-val"><?php echo v($personal['cuil']); ?></span></div>
            <div class="p-item"><span class="p-lbl">Fecha Nac.</span><span
                    class="p-val"><?php echo d($personal['fecha_nacimiento']); ?></span></div>
            <div class="p-item"><span class="p-lbl">Teléfono</span><span
                    class="p-val"><?php echo v($personal['telefono_personal']); ?></span></div>

            <div class="p-item span-2"><span class="p-lbl">Domicilio Real</span><span
                    class="p-val"><?php echo v($personal['domicilio']); ?></span></div>
            <div class="p-item"><span class="p-lbl">Estado Civil</span><span
                    class="p-val"><?php echo v($personal['estado_civil']); ?></span></div>
            <div class="p-item"><span class="p-lbl">Nacionalidad</span><span class="p-val">ARGENTINA</span></div>
        </div>
    </div>

    <!-- 1. ADMIN & BANK -->
    <div class="sec-title">1. Datos Administrativos, Bancarios y Digitales</div>
    <div class="grid-6">
        <div class="f-group span-2"><span class="f-lbl">CBU / Alias</span><span
                class="f-val"><?php echo v($personal['cbu_alias']); ?></span></div>
        <div class="f-group span-4"><span class="f-lbl">Tareas Habilitadas</span><span class="f-val"><?php
        $t = json_decode($personal['tareas_desempenadas'], true);
        echo is_array($t) ? implode(', ', $t) : v($personal['tareas_desempenadas']);
        ?></span></div>
        <div class="f-group span-6"><span class="f-lbl">Link Legajo Digital</span><span
                class="f-val"><?php echo v($personal['link_legajo_digital']); ?></span></div>
    </div>

    <!-- 2. SAFETY & EPP -->
    <div class="sec-title">2. Seguridad Vial, EPP y Talles</div>
    <div class="grid-6">
        <div class="f-group span-2"><span class="f-lbl">Licencia Conducir</span><span
                class="f-val"><?php echo $personal['tiene_carnet'] ? 'CLASE ' . $personal['tipo_carnet'] : 'NO'; ?></span>
        </div>
        <div class="f-group span-2"><span class="f-lbl">Vencimiento Lic.</span><span
                class="f-val"><?php echo $personal['tiene_carnet'] ? d($personal['vencimiento_carnet_conducir']) : '-'; ?></span>
        </div>
        <div class="f-group span-2"><span class="f-lbl">Última Entrega EPP</span><span
                class="f-val"><?php echo d($personal['fecha_ultima_entrega_epp']); ?></span></div>
    </div>
    <table>
        <tr>
            <th width="25%">CAMISA / CHAQUETA</th>
            <th width="25%">PANTALÓN</th>
            <th width="25%">CALZADO SEGURIDAD</th>
            <th width="25%">REMERA / OTROS</th>
        </tr>
        <tr>
            <td><?php echo v($personal['talle_camisa']); ?></td>
            <td><?php echo v($personal['talle_pantalon']); ?></td>
            <td><?php echo v($personal['talle_calzado']); ?></td>
            <td><?php echo v($personal['talle_remera']); ?></td>
        </tr>
    </table>

    <!-- 3. HEALTH & FAMILY -->
    <div class="sec-title">3. Salud, Emergencia y Grupo Familiar</div>
    <div class="grid-6">
        <div class="f-group span-2"><span class="f-lbl">Obra Social</span><span
                class="f-val"><?php echo v($personal['obra_social']); ?></span></div>
        <div class="f-group span-2"><span class="f-lbl">Teléfono O.S.</span><span
                class="f-val"><?php echo v($personal['obra_social_telefono']); ?></span></div>
        <div class="f-group span-2"><span class="f-lbl">Grupo Sanguíneo</span><span
                class="f-val"><?php echo v($personal['grupo_sanguineo']); ?></span></div>

        <div class="f-group span-2"><span class="f-lbl">Seguro ART</span><span
                class="f-val"><?php echo v($personal['seguro_art']); ?></span></div>
        <div class="f-group span-4"><span class="f-lbl">Examen Preocupacional</span><span
                class="f-val"><?php echo d($personal['fecha_examen_preocupacional']); ?> -
                <?php echo v($personal['empresa_examen_preocupacional']); ?></span></div>

        <div class="f-group span-6"><span class="f-lbl">Alergias / Condiciones Médicas</span><span
                class="f-val multiline"><?php echo v($personal['alergias_condiciones']); ?></span></div>

        <div class="f-group span-3"><span class="f-lbl">Contacto Emergencia</span><span
                class="f-val"><?php echo v($personal['contacto_emergencia_nombre']); ?>
                (<?php echo v($personal['contacto_emergencia_parentesco']); ?>)</span></div>
        <div class="f-group span-3"><span class="f-lbl">Tel. Emergencia</span><span
                class="f-val"><?php echo v($personal['numero_emergencia']); ?></span></div>

        <div class="f-group span-6"><span class="f-lbl">Personas a Cargo / Grupo Familiar</span><span
                class="f-val multiline"><?php echo v($personal['personas_a_cargo']); ?></span></div>
    </div>

    <!-- LEGAL -->
    <div class="sec-title">4. Declaración Jurada</div>
    <div class="legal">
        <b>DECLARACIÓN JURADA H&S Y DATOS PERSONALES:</b> Declaro que los datos consignados son veraces y exactos. Me
        comprometo a notificar cualquier cambio dentro de las 48hs.
        Asimismo, declaro haber recibido de <b>RECURSOS GLOBALES S.A.</b> la inducción de seguridad, Reglamento Interno
        y Elementos de Protección Personal (EPP), comprometiéndome a usarlos obligatoriamente y conservarlos.
        Tomo conocimiento de los riesgos laborales del puesto y acepto cumplir las normas de seguridad vigentes.
        <br>
        <i>* La firma al pie implica plena conformidad con lo expuesto.</i>
    </div>

    <!-- SIGS -->
    <div class="sigs">
        <div class="sig-box">
            <br>
            <span class="sig-txt"><?php echo v($personal['nombre_apellido']); ?></span>
            <span class="sig-sub">FIRMA DEL EMPLEADO - DNI: <?php echo v($personal['dni']); ?></span>
        </div>
        <div class="sig-box">
            <br>
            <span class="sig-txt">RECURSOS HUMANOS</span>
            <span class="sig-sub">APROBACIÓN DE INGRESO</span>
        </div>
    </div>

</body>

</html>