<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: list.php');
    exit;
}

// Get order with provider info
$stmt = $pdo->prepare("
    SELECT po.*, p.name as provider_name, p.contact_name, p.contact_email
    FROM purchase_orders po 
    JOIN providers p ON po.provider_id = p.id 
    WHERE po.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: list.php');
    exit;
}

// Get items for summary
$stmtItems = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item['quantity'] * $item['price_unit'];
}

// Default email template
$providerEmail = $order['contact_email'] ?? '';
$providerName = $order['contact_name'] ?: $order['provider_name'];

$defaultSubject = "Orden de Compra " . $order['po_number'];

$defaultBody = "Estimado/a {$providerName},

Por medio del presente, le hacemos llegar la Orden de Compra N° {$order['po_number']} por un monto total de $" . number_format($total, 2) . ".

Detalle de la orden:
";

foreach ($items as $item) {
    $defaultBody .= "- {$item['item_description']}: {$item['quantity']} unidades @ $" . number_format($item['price_unit'], 2) . "\n";
}

if ($order['delivery_date_committed']) {
    $defaultBody .= "\nFecha de entrega comprometida: " . date('d/m/Y', strtotime($order['delivery_date_committed']));
}

$defaultBody .= "

Por favor, confirmar recepción de esta orden y disponibilidad de los productos/servicios solicitados.

Adjunto encontrará el documento PDF con el detalle completo de la orden.

Saludos cordiales,
{$userName}
Departamento de Compras";

// Handle form submission - mark as sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_sent'])) {
    $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'enviada' WHERE id = ?");
    $stmt->execute([$orderId]);
    header('Location: list.php?sent=' . $order['po_number']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar OC
        <?= htmlspecialchars($order['po_number']) ?> - Módulo de Compras
    </title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .send-container {
            max-width: 800px;
        }

        .email-preview {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .email-header {
            background: var(--bg-body);
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .email-field {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .email-field label {
            width: 80px;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .email-field input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
        }

        .email-body {
            padding: 1rem;
        }

        .email-body textarea {
            width: 100%;
            min-height: 350px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 0.875rem;
            line-height: 1.5;
            resize: vertical;
        }

        .attachment-preview {
            background: #FEF3C7;
            border: 1px dashed #F59E0B;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            margin: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .order-summary {
            background: var(--bg-body);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }

        .order-summary h3 {
            margin-bottom: 0.5rem;
        }

        .actions-row {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }

        .warning-box {
            background: #DBEAFE;
            border: 1px solid #3B82F6;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 1rem;
            font-size: 0.875rem;
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
            <div class="send-container">
                <h1 style="margin-bottom: 1.5rem;">✉️ Enviar Orden de Compra</h1>

                <div class="order-summary">
                    <h3>
                        <?= htmlspecialchars($order['po_number']) ?>
                    </h3>
                    <p>Proveedor: <strong>
                            <?= htmlspecialchars($order['provider_name']) ?>
                        </strong></p>
                    <p>Total: <strong>$
                            <?= number_format($total, 2) ?>
                        </strong></p>
                </div>

                <div class="email-preview">
                    <div class="email-header">
                        <div class="email-field">
                            <label>Para:</label>
                            <input type="email" id="email-to" value="<?= htmlspecialchars($providerEmail) ?>"
                                placeholder="correo@proveedor.com">
                        </div>
                        <div class="email-field">
                            <label>Asunto:</label>
                            <input type="text" id="email-subject" value="<?= htmlspecialchars($defaultSubject) ?>">
                        </div>
                    </div>

                    <div class="attachment-preview">
                        📎 <strong>Adjunto:</strong>
                        <?= htmlspecialchars($order['po_number']) ?>.pdf
                        <a href="pdf.php?id=<?= $orderId ?>" target="_blank"
                            style="margin-left: auto; color: var(--info-color);">Ver PDF</a>
                    </div>

                    <div class="email-body">
                        <textarea id="email-body"><?= htmlspecialchars($defaultBody) ?></textarea>
                    </div>
                </div>

                <div class="warning-box">
                    💡 <strong>Nota:</strong> Al hacer clic en "Abrir en Email", se abrirá tu cliente de correo con el
                    mensaje pre-cargado.
                    Recuerda adjuntar manualmente el PDF de la orden antes de enviar.
                </div>

                <div class="actions-row">
                    <button onclick="openEmailClient()" class="btn btn-primary btn-lg">
                        📧 Abrir en Email
                    </button>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="mark_sent" value="1">
                        <button type="submit" class="btn btn-lg" style="background: var(--success-color); color: white;"
                            onclick="return confirm('¿Confirmas que ya enviaste el email al proveedor?')">
                            ✓ Marcar como Enviada
                        </button>
                    </form>

                    <a href="list.php" class="btn btn-lg" style="background: var(--bg-body);">Cancelar</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openEmailClient() {
            const to = document.getElementById('email-to').value;
            const subject = encodeURIComponent(document.getElementById('email-subject').value);
            const body = encodeURIComponent(document.getElementById('email-body').value);

            // Open mailto link
            window.location.href = `mailto:${to}?subject=${subject}&body=${body}`;
        }
    </script>
</body>

</html>