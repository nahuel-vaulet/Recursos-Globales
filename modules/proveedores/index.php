<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch Providers with their Primary Contact Info
$provs = $pdo->query("SELECT p.*, c.nombre_vendedor, c.telefono_contacto, c.email_vendedor 
                      FROM proveedores p 
                      LEFT JOIN proveedores_contactos c ON p.id_proveedor = c.id_proveedor 
                      GROUP BY p.id_proveedor 
                      ORDER BY p.razon_social")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="padding: 0 20px;">
    <div class="card">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="fas fa-truck"></i> Gestión de Proveedores</h1>
            <a href="form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Proveedor</a>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--color-primary-dark); color: white;">
                    <th style="padding: 10px; text-align: left;">Empresa</th>
                    <th style="padding: 10px; text-align: left;">Dirección</th>
                    <th style="padding: 10px; text-align: left;">Contacto Principal</th>
                    <th style="padding: 10px; text-align: left;">Teléfono / Email</th>
                    <th style="padding: 10px; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($provs as $p): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <strong><?php echo $p['razon_social']; ?></strong><br>
                            <span style="font-size:0.8em; color:#666;">CUIT: <?php echo $p['cuit'] ?? '-'; ?></span>
                        </td>
                        <td style="padding: 10px;"><?php echo $p['direccion'] ?? '-'; ?></td>
                        <td style="padding: 10px;"><?php echo $p['nombre_vendedor'] ?? '-'; ?></td>
                        <td style="padding: 10px;">
                            <div><i class="fas fa-phone"></i> <?php echo $p['telefono_contacto'] ?? '-'; ?></div>
                            <div style="font-size:0.9em;"><i class="fas fa-envelope"></i>
                                <?php echo $p['email_vendedor'] ?? '-'; ?></div>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <a href="form.php?id=<?php echo $p['id_proveedor']; ?>" class="btn btn-outline"
                                style="padding: 5px 10px;"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>