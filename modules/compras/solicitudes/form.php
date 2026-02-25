<?php
require_once '../../../config/database.php';
require_once '../../../includes/header.php';

if (!tienePermiso('compras')) {
    header('Location: ../../../index.php?msg=forbidden');
    exit;
}

$userId = $_SESSION['usuario_id']; // ID from global session
$userName = $_SESSION['usuario_nombre'] ?? 'Usuario';
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
                INSERT INTO compras_solicitudes (id_usuario, titulo, descripcion, urgencia, unidad_negocio, ubicacion, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'enviada')
            ");
            $stmt->execute([$userId, $title, $description, $urgency, $unit, $location]);
            $requestId = $pdo->lastInsertId();

            // Insert items
            $stmtItem = $pdo->prepare("INSERT INTO compras_items_solicitud (id_solicitud, item, cantidad, unidad) VALUES (?, ?, ?, ?)");
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
            header('Location: index.php?msg=created');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al crear la solicitud: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid" style="padding: 0 20px;">
    <!-- Header -->
    <div style="margin-bottom: 25px;">
        <h1 style="margin: 0;"><i class="fas fa-file-invoice"></i> Nueva Solicitud</h1>
        <p style="margin: 5px 0 0; color: #666;">Crear una nueva solicitud de compra</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"
            style="background-color: #FEE2E2; color: #B91C1C; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card" style="padding: 25px;">
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Full Width Title -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Título de la
                    solicitud *</label>
                <input type="text" name="title" class="form-control" required
                    placeholder="Ej: Materiales para obra Norte"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Urgencia</label>
                <select name="urgency" class="form-control"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="baja">Baja</option>
                    <option value="media">Media</option>
                    <option value="alta">Alta</option>
                    <option value="critica">Crítica</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Unidad de
                    Negocio</label>
                <input type="text" name="unit_of_business" class="form-control" placeholder="Ej: Obras Civiles"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Ubicación /
                    Destino</label>
                <input type="text" name="location" class="form-control" placeholder="Ej: Obra Edificio Central, Piso 5"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 8px;">Descripción
                    adicional</label>
                <textarea name="description" class="form-control" rows="3"
                    placeholder="Detalles, especificaciones, notas..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
            </div>
        </div>

        <div class="items-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h3 style="margin-bottom: 15px; font-size: 1.1em; color: var(--color-primary);">Ítems a solicitar</h3>
            <div id="items-container">
                <div class="item-row"
                    style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 15px; align-items: end;">
                    <div class="form-group">
                        <label class="form-label"
                            style="font-size: 0.9em; margin-bottom: 5px; display: block;">Descripción *</label>
                        <input type="text" name="items[0][name]" class="form-control" required
                            placeholder="Ej: Cemento Portland"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label"
                            style="font-size: 0.9em; margin-bottom: 5px; display: block;">Cantidad</label>
                        <input type="number" name="items[0][quantity]" class="form-control" value="1" min="0.01"
                            step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label"
                            style="font-size: 0.9em; margin-bottom: 5px; display: block;">Unidad</label>
                        <input type="text" name="items[0][unit]" class="form-control" value="unidades"
                            placeholder="kg, m, unidades"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)"
                        style="background: #FEE2E2; color: #B91C1C; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-outline" onclick="addItem()"
                style="margin-top: 10px; padding: 8px 15px; border: 1px solid var(--color-primary); color: var(--color-primary); background: white; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-plus"></i> Agregar ítem
            </button>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
            <a href="index.php" class="btn btn-light"
                style="padding: 10px 20px; background: #f3f4f6; text-decoration: none; color: #333; border-radius: 6px;">Cancelar</a>
            <button type="submit" class="btn btn-primary"
                style="padding: 10px 20px; background: var(--color-primary); color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
        </div>
    </form>
</div>

<script>
    let itemIndex = 1;
    function addItem() {
        const container = document.getElementById('items-container');
        const html = `
            <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 15px; align-items: end;">
                <div class="form-group">
                    <input type="text" name="items[${itemIndex}][name]" class="form-control" placeholder="Descripción del ítem"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="0.01" step="0.01"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <input type="text" name="items[${itemIndex}][unit]" class="form-control" value="unidades"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <button type="button" class="btn-remove" onclick="removeItem(this)" 
                    style="background: #FEE2E2; color: #B91C1C; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        itemIndex++;
    }

    function removeItem(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('.item-row').remove();
        } else {
            alert('Debe haber al menos un ítem');
        }
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>