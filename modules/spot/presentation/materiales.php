<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch materials
$materials = $pdo->query("SELECT id_material, nombre, codigo, unidad_medida FROM maestro_materiales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch personnel
$personal = $pdo->query("SELECT id_personal, nombre_apellido, rol FROM personal ORDER BY nombre_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch squads
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="padding: 20px;">
    <div class="card" style="padding: 20px; border-top: 4px solid var(--accent-primary);">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-truck-loading"></i> Entrega de Materiales (Remito Múltiple)
        </h2>

        <form id="remitoForm">
            <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label">Personal Entrega</label>
                    <select name="id_personal_entrega" class="form-control" required>
                        <option value="">Seleccione personal...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= $p['id_personal'] ?>">
                                <?= htmlspecialchars($p['nombre_apellido']) ?> (
                                <?= $p['rol'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label">Personal Recepción</label>
                    <select name="id_personal_recepcion" class="form-control" required>
                        <option value="">Seleccione personal...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= $p['id_personal'] ?>">
                                <?= htmlspecialchars($p['nombre_apellido']) ?> (
                                <?= $p['rol'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label">Cuadrilla Destino</label>
                    <select name="id_cuadrilla_destino" class="form-control" required>
                        <option value="">Seleccione cuadrilla...</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?= $c['id_cuadrilla'] ?>">
                                <?= htmlspecialchars($c['nombre_cuadrilla']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label">Destino de Obra (Opcional)</label>
                    <input type="text" name="destino_obra" class="form-control" placeholder="Ej: Obra Calle 123">
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h3><i class="fas fa-list"></i> Selección de Materiales</h3>
                <div class="table-container" style="max-height: 400px; overflow-y: auto; margin-top: 15px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Sel.</th>
                                <th>Material</th>
                                <th style="width: 150px;">Cantidad</th>
                                <th>Unidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $m): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="mat-checkbox" data-id="<?= $m['id_material'] ?>">
                                    </td>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($m['nombre']) ?>
                                        </strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($m['codigo']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control mat-qty"
                                            id="qty_<?= $m['id_material'] ?>" disabled placeholder="0.00">
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($m['unidad_medida']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right;">
                <button type="button" class="btn btn-outline"
                    onclick="window.location.href='index.php'">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="fas fa-file-invoice"></i> Realizar Entrega a Cuadrilla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.mat-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const qtyInput = document.getElementById('qty_' + this.dataset.id);
            qtyInput.disabled = !this.checked;
            if (this.checked) qtyInput.focus();
            else qtyInput.value = '';
        });
    });

    document.getElementById('remitoForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        const formData = new FormData(this);
        const items = [];
        document.querySelectorAll('.mat-checkbox:checked').forEach(cb => {
            const id = cb.dataset.id;
            const qty = document.getElementById('qty_' + id).value;
            if (qty > 0) {
                items.push({ id_material: id, cantidad: qty });
            }
        });

        if (items.length === 0) {
            showToast('Debe seleccionar al menos un material con cantidad.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-invoice"></i> Realizar Entrega a Cuadrilla';
            return;
        }

        const payload = {
            id_personal_entrega: formData.get('id_personal_entrega'),
            id_personal_recepcion: formData.get('id_personal_recepcion'),
            id_cuadrilla_destino: formData.get('id_cuadrilla_destino'),
            destino_obra: formData.get('destino_obra'),
            items: items
        };

        try {
            const response = await fetch('api/save_remito.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result.status === 'success') {
                showToast('✓ Remito generado: ' + result.nro_remito, 'success');
                setTimeout(() => window.location.href = 'remito.php?id=' + result.id_remito, 1500);
            } else {
                alert('Error [' + result.errorId + ']: ' + result.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-invoice"></i> Realizar Entrega a Cuadrilla';
            }
        } catch (err) {
            alert('Error de conexión. Intente nuevamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-invoice"></i> Realizar Entrega a Cuadrilla';
        }
    });
</script>

<style>
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--accent-primary);
        color: var(--bg-primary);
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--text-muted);
        color: var(--text-secondary);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        text-align: left;
        padding: 12px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        color: var(--text-muted);
        font-size: 0.8em;
        text-transform: uppercase;
    }

    .table td {
        padding: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>