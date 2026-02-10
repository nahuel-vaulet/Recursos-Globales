<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = $_GET['id'] ?? null;
$prov = null;
$contact = null;

// Variables para pre-llenar los selects si existen en observaciones
$selected_pagos = '';
$selected_dias = '';
$selected_horario = '';
$selected_entrega = '';
$obs_text = ''; // Initialize to avoid warning

if ($id) {
    // Fetch Provider
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
    $stmt->execute([$id]);
    $prov = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch First Contact
    $stmt2 = $pdo->prepare("SELECT * FROM proveedores_contactos WHERE id_proveedor = ? LIMIT 1");
    $stmt2->execute([$id]);
    $contact = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Parse Observations to try and pre-select dropdowns (Simple heuristic)
    // Format expecting: "Pagos: X | Días: Y | Horario: Z | Entrega: W | Obs: ..."
    $obs_text = $contact['observaciones'] ?? '';
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h1><?php echo $id ? 'Editar' : 'Nuevo'; ?> Proveedor</h1>

    <form action="save.php" method="POST">
        <?php if ($id): ?>
            <input type="hidden" name="id_proveedor" value="<?php echo $id; ?>">
            <input type="hidden" name="id_contacto" value="<?php echo $contact['id_contacto'] ?? ''; ?>">
        <?php endif; ?>

        <!-- Datos Empresa -->
        <h3
            style="color: var(--color-primary); border-bottom: 2px solid var(--color-primary-light); margin-bottom: 15px;">
            <i class="fas fa-building"></i> Datos Empresa
        </h3>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-weight: 500;">Razón Social *</label>
                <input type="text" name="razon_social" required value="<?php echo $prov['razon_social'] ?? ''; ?>"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div>
                <label style="font-weight: 500;">CUIT</label>
                <input type="text" name="cuit" value="<?php echo $prov['cuit'] ?? ''; ?>" placeholder="20-12345678-9"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
        </div>
        
        <div style="margin-bottom: 15px;">
             <label style="font-weight: 500;">Dirección Física</label>
             <input type="text" name="direccion" value="<?php echo $prov['direccion'] ?? ''; ?>"
                 placeholder="Calle, Altura, Ciudad"
                 style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
        </div>

        <!-- Datos Contacto -->
        <h3 style="color: var(--color-neutral-dark); border-bottom: 2px solid #ccc; margin-bottom: 15px;">
            <i class="fas fa-user-tie"></i> Comercial
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-weight: 500;">Vendedor</label>
                <input type="text" name="nombre_vendedor" value="<?php echo $contact['nombre_vendedor'] ?? ''; ?>"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div>
                <label style="font-weight: 500;">Teléfono</label>
                <input type="text" name="telefono_contacto" value="<?php echo $contact['telefono_contacto'] ?? ''; ?>"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div>
                <label style="font-weight: 500;">Email</label>
                <input type="email" name="email_vendedor" value="<?php echo $contact['email_vendedor'] ?? ''; ?>"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
        </div>

        <!-- Logística y Pagos (Desplegables) -->
        <h3 style="color: var(--color-success); border-bottom: 2px solid #ccc; margin-bottom: 15px;">
            <i class="fas fa-handshake"></i> Logística y Pagos
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-weight: 500;">Condición de Pago</label>
                <select name="condicion_pago"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="">-- Seleccionar --</option>
                    <option value="Contado Efectivo">Contado Efectivo (Caja Chica)</option>
                    <option value="Transferencia Inmediata">Transferencia Inmediata</option>
                    <option value="Cheque 30 Días">Cheque 30 Días</option>
                    <option value="Cheque 30/60 Días">Cheque 30/60 Días</option>
                    <option value="Cuenta Corriente Mensual">Cuenta Corriente Mensual</option>
                </select>
            </div>
            <div>
                <label style="font-weight: 500;">Modalidad de Entrega</label>
                <select name="modalidad_entrega"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="">-- Seleccionar --</option>
                    <option value="Retiro en Local">Retiro en Local (Vamos nosotros)</option>
                    <option value="Envío a Obra">Envío a Obra (Flete incluido)</option>
                    <option value="Envío a Obra (Flete aparte)">Envío a Obra (Flete aparte)</option>
                    <option value="Envío a Oficina (Acopio)">Envío a Oficina (Acopio)</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-weight: 500;">Días de Atención</label>
                <select name="dias_atencion"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="Lun a Vie">Lunes a Viernes</option>
                    <option value="Lun a Sab (Mediodía)">Lun a Sab (Mediodía)</option>
                    <option value="Lun a Sab (Completo)">Lun a Sab (Completo)</option>
                </select>
            </div>
            <div>
                <label style="font-weight: 500;">Horarios</label>
                <select name="horarios_atencion"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="Corrido (08 a 17)">Corrido (08:00 a 17:00)</option>
                    <option value="Comercial (08-12 / 15-19)">Comercial (08-12 / 15-19)</option>
                    <option value="Construcción (07-15)">Construcción (07:00 a 15:00)</option>
                    <option value="Solo Mañana">Solo Mañana</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: 500;">Notas Adicionales</label>
            <textarea name="notas_extra" rows="2" placeholder="Cualquier otro detalle..."
                style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;"><?php
                // Show raw obs if it doesn't look formatted, otherwise empty to avoid clutter
                echo (!strpos($obs_text, '|')) ? $obs_text : '';
                ?></textarea>
        </div>

        <div style="text-align: right;">
            <a href="index.php" class="btn btn-outline" style="margin-right: 10px;">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Proveedor</button>
        </div>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>