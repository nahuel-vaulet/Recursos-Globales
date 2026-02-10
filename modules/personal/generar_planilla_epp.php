<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

$id = $_GET['id'] ?? null;
if (!$id)
    die("ID de personal no especificado.");

$stmt = $pdo->prepare("SELECT * FROM personal WHERE id_personal = ?");
$stmt->execute([$id]);
$personal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$personal)
    die("Personal no encontrado.");

$items_epp = [
    'Camisa Grafa' => $personal['talle_camisa'],
    'Pantalón Grafa' => $personal['talle_pantalon'],
    'Remera' => $personal['talle_remera'],
    'Botines de Seguridad' => $personal['talle_calzado'],
    'Casco de Seguridad' => '',
    'Antiparras' => '',
    'Guantes de Trabajo' => '',
    'Protector Auditivo' => '',
    'Chaleco Reflectivo' => '',
    'Arnés de Seguridad' => ''
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Planilla Entrega EPP -
        <?php echo htmlspecialchars($personal['nombre_apellido']); ?>
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .info-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #eee;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #0073A8; color: white; border: none; cursor: pointer; border-radius: 4px;">Imprimir
            / Guardar PDF</button>
    </div>

    <div class="header">
        <h2>CONSTANCIA DE ENTREGA DE ROPA DE TRABAJO Y EPP</h2>
        <p>Recursos Globales - Gestión de Personal</p>
    </div>

    <div class="info-box">
        <div class="info-row">
            <span><span class="label">Empleado:</span>
                <?php echo htmlspecialchars($personal['nombre_apellido']); ?>
            </span>
            <span><span class="label">DNI:</span>
                <?php echo htmlspecialchars($personal['dni']); ?>
            </span>
        </div>
        <div class="info-row">
            <span><span class="label">Legajo/ID:</span>
                <?php echo $personal['id_personal']; ?>
            </span>
            <span><span class="label">Fecha de Entrega:</span> __________________________</span>
        </div>
        <div class="info-row">
            <span><span class="label">Cargo/Rol:</span>
                <?php echo htmlspecialchars($personal['rol']); ?>
            </span>
        </div>
    </div>

    <p>Por medio de la presente, declaro recibir los siguientes elementos de protección personal y ropa de trabajo,
        comprometiéndome a utilizarlos correctamente durante la jornada laboral y a mantenerlos en buen estado.</p>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Elemento</th>
                <th style="width: 20%;">Talle / Detalle</th>
                <th style="width: 15%;">Cantidad</th>
                <th style="width: 25%;">Firma Recepción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items_epp as $item => $talle): ?>
                <tr>
                    <td>
                        <?php echo $item; ?>
                    </td>
                    <td>
                        <?php echo $talle ? $talle : '_____________'; ?>
                    </td>
                    <td>1</td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td>Otro: ______________________</td>
                <td>_____________</td>
                <td>___</td>
                <td></td>
            </tr>
            <tr>
                <td>Otro: ______________________</td>
                <td>_____________</td>
                <td>___</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            <br><br>
            Firma del Empleado
        </div>
        <div class="signature-box">
            <br><br>
            Firma del Responsable de Entrega
        </div>
    </div>
</body>

</html>