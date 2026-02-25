<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $taxId = trim($_POST['tax_id'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contractType = $_POST['contract_type'] ?? 'spot';
    $paymentTerms = trim($_POST['payment_terms'] ?? '');
    $paymentLimit = floatval($_POST['payment_limit'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($name)) {
        $error = 'El nombre del proveedor es obligatorio';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO providers (name, tax_id, contact_name, contact_email, phone, address, contract_type, payment_terms, payment_limit, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $taxId ?: null,
                $contactName ?: null,
                $contactEmail ?: null,
                $phone ?: null,
                $address ?: null,
                $contractType,
                $paymentTerms ?: null,
                $paymentLimit > 0 ? $paymentLimit : null,
                $notes ?: null
            ]);
            header('Location: list.php?created=1');
            exit;
        } catch (Exception $e) {
            $error = 'Error al crear proveedor: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Proveedor - Módulo de Compras</title>
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

        .section-title {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            margin: 1.5rem 0 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title:first-of-type {
            margin-top: 0;
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

        .optional-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: normal;
        }

        @media (max-width: 768px) {
            .form-grid {
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
                <a href="../dashboard/index.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Dashboard</a>
                <a href="../requests/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Solicitudes</a>
                <a href="list.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Proveedores</a>
                <a href="../orders/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Órdenes de
                    Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤
                    <?= htmlspecialchars($userName) ?>
                </p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <h1 style="margin-bottom: 1.5rem;">Nuevo Proveedor</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="card">
                <div class="section-title">Información Básica</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre del Proveedor *</label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="Ej: Materiales del Norte S.A.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUT / NIF / Tax ID <span
                                class="optional-label">(opcional)</span></label>
                        <input type="text" name="tax_id" class="form-control" placeholder="Ej: 12.345.678-9">
                    </div>
                </div>

                <div class="section-title">Contacto</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre de Contacto <span
                                class="optional-label">(opcional)</span></label>
                        <input type="text" name="contact_name" class="form-control" placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email de Contacto <span
                                class="optional-label">(opcional)</span></label>
                        <input type="email" name="contact_email" class="form-control"
                            placeholder="Ej: ventas@proveedor.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono <span class="optional-label">(opcional)</span></label>
                        <input type="text" name="phone" class="form-control" placeholder="Ej: +56 9 1234 5678">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Dirección <span class="optional-label">(opcional)</span></label>
                        <input type="text" name="address" class="form-control"
                            placeholder="Ej: Av. Principal 123, Ciudad">
                    </div>
                </div>

                <div class="section-title">Condiciones Comerciales</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tipo de Contrato <span
                                class="optional-label">(opcional)</span></label>
                        <select name="contract_type" class="form-control">
                            <option value="spot">Spot (compra puntual)</option>
                            <option value="marco">Marco (contrato vigente)</option>
                            <option value="exclusivo">Exclusivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pago <span class="optional-label">(opcional)</span></label>
                        <select name="payment_terms" class="form-control">
                            <option value="">Sin especificar</option>
                            <option value="contado">Contado</option>
                            <option value="30_dias">30 días</option>
                            <option value="60_dias">60 días</option>
                            <option value="90_dias">90 días</option>
                            <option value="anticipado">Pago anticipado</option>
                            <option value="contra_entrega">Contra entrega</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Límite de Crédito ($) <span
                                class="optional-label">(opcional)</span></label>
                        <input type="number" name="payment_limit" class="form-control" min="0" step="0.01"
                            placeholder="Ej: 50000.00">
                    </div>
                </div>

                <div class="section-title">Notas Adicionales</div>
                <div class="form-group">
                    <label class="form-label">Observaciones <span class="optional-label">(opcional)</span></label>
                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Cualquier información relevante sobre el proveedor..."></textarea>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                    <a href="list.php" class="btn" style="background: var(--bg-body);">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
</body>

</html>