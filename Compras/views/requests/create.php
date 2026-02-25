<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'baja';
    $location = trim($_POST['location'] ?? '');
    $unit = trim($_POST['unit_of_business'] ?? '');
    $items = $_POST['items'] ?? [];

    if (empty($title)) {
        $error = 'El título es obligatorio';
    } elseif (empty($items) || empty($items[0]['name'])) {
        $error = 'Debes agregar al menos un ítem';
    } else {
        try {
            $pdo->beginTransaction();

            // Insert request
            $stmt = $pdo->prepare("
                INSERT INTO purchase_requests (user_id, title, description, urgency, unit_of_business, location, status)
                VALUES (?, ?, ?, ?, ?, ?, 'enviada')
            ");
            $stmt->execute([$userId, $title, $description, $urgency, $unit, $location]);
            $requestId = $pdo->lastInsertId();

            // Insert items
            $stmtItem = $pdo->prepare("INSERT INTO request_items (request_id, item_name, quantity, unit) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                if (!empty($item['name'])) {
                    $stmtItem->execute([
                        $requestId,
                        $item['name'],
                        floatval($item['quantity'] ?? 1),
                        $item['unit'] ?? 'unidades'
                    ]);
                }
            }

            $pdo->commit();
            header('Location: my_requests.php?created=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al crear la solicitud: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .items-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: end;
        }

        .btn-remove {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-md);
            cursor: pointer;
        }

        .btn-add {
            background: var(--success-color);
            color: white;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .item-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="my_requests.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Mis Solicitudes</a>
                <a href="create.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Nueva Solicitud</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤
                    <?= htmlspecialchars($userName) ?>
                </p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <h1 style="margin-bottom: 1.5rem;">Nueva Solicitud de Compra</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="card">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Título de la solicitud *</label>
                        <input type="text" name="title" class="form-control" required
                            placeholder="Ej: Materiales para obra Norte">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Urgencia</label>
                        <select name="urgency" class="form-control">
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unidad de Negocio</label>
                        <input type="text" name="unit_of_business" class="form-control" placeholder="Ej: Obras Civiles">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Ubicación / Destino</label>
                        <input type="text" name="location" class="form-control"
                            placeholder="Ej: Obra Edificio Central, Piso 5">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Descripción adicional</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Detalles, especificaciones, notas..."></textarea>
                    </div>
                </div>

                <div class="items-section">
                    <h3 style="margin-bottom: 1rem;">Ítems a solicitar</h3>
                    <div id="items-container">
                        <div class="item-row">
                            <div class="form-group">
                                <label class="form-label">Descripción del ítem *</label>
                                <input type="text" name="items[0][name]" class="form-control" required
                                    placeholder="Ej: Cemento Portland">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="items[0][quantity]" class="form-control" value="1" min="0.01"
                                    step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unidad</label>
                                <input type="text" name="items[0][unit]" class="form-control" value="unidades"
                                    placeholder="kg, m, unidades">
                            </div>
                            <button type="button" class="btn-remove" onclick="removeItem(this)"
                                title="Eliminar">✕</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-add" onclick="addItem()" style="margin-top: 0.5rem;">+ Agregar
                        ítem</button>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Enviar Solicitud</button>
                    <a href="my_requests.php" class="btn" style="background: var(--bg-body);">Cancelar</a>
                </div>
            </form>
        </main>
    </div>

    <script>
        let itemIndex = 1;
        function addItem() {
            const container = document.getElementById('items-container');
            const html = `
                <div class="item-row">
                    <div class="form-group">
                        <input type="text" name="items[${itemIndex}][name]" class="form-control" placeholder="Descripción del ítem">
                    </div>
                    <div class="form-group">
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="0.01" step="0.01">
                    </div>
                    <div class="form-group">
                        <input type="text" name="items[${itemIndex}][unit]" class="form-control" value="unidades">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">✕</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            itemIndex++;
        }
        function removeItem(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('.item-row').remove();
            }
        }
    </script>
</body>

</html>