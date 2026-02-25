<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';



require_once 'views/squads_data_loader.php';
?>

<div class="container-fluid" style="padding: 0 20px;">
    <!-- Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;"><i class="fas fa-gas-pump"></i> Gestión de Combustibles</h2>
        <a href="../stock/index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver a
            Stock</a>
    </div>

    <!-- Include Dashboard Partial -->
    <?php include 'views/dashboard.php'; ?>

    <!-- History / Movements (Similar to Stock but filtered for Fuel) -->
    <div class="card" style="margin-top: 30px; padding: 20px;">
        <h3><i class="fas fa-history"></i> Últimos Movimientos de Combustible</h3>
        <?php
        // Self-Healing Schema Check (Ensure 'foto_ticket' exists to prevent crash)
        try {
            $pdo->query("SELECT foto_ticket FROM combustibles_cargas LIMIT 1");
        } catch (Exception $e) {
            try {
                $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN foto_ticket VARCHAR(255) NULL AFTER nro_factura");
            } catch (Exception $ex) {
            }
        }

        // Fetch last 50 movements (Union Cargas and Despachos)
        $sql = "
        (SELECT 'Carga' as tipo, c.fecha_hora, c.litros, t.nombre as tanque, c.proveedor as detalle, u.nombre as usuario, c.foto_ticket
         FROM combustibles_cargas c 
         JOIN combustibles_tanques t ON c.id_tanque = t.id_tanque
         LEFT JOIN usuarios u ON c.usuario_id = u.id_usuario)
        UNION
        (SELECT 'Despacho' as tipo, d.fecha_hora, d.litros, t.nombre as tanque, CONCAT(v.patente, ' - ', d.usuario_conductor) as detalle, u.nombre as usuario, NULL as foto_ticket
         FROM combustibles_despachos d
         JOIN combustibles_tanques t ON d.id_tanque = t.id_tanque
         JOIN vehiculos v ON d.id_vehiculo = v.id_vehiculo
         LEFT JOIN usuarios u ON d.usuario_despacho = u.id_usuario)
        ORDER BY fecha_hora DESC LIMIT 50";

        $movs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Tanque</th>
                    <th>Litros</th>
                    <th>Detalle (Prov / Vehículo)</th>
                    <th>Usuario</th>
                    <th>Ticket</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movs as $m):
                    $color = ($m['tipo'] == 'Carga') ? 'text-success' : 'text-primary';
                    $sign = ($m['tipo'] == 'Carga') ? '+' : '-';
                    ?>
                    <tr>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($m['fecha_hora'])); ?>
                        </td>
                        <td class="<?php echo $color; ?>"><strong>
                                <?php echo $m['tipo']; ?>
                            </strong></td>
                        <td>
                            <?php echo $m['tanque']; ?>
                        </td>
                        <td style="font-weight:bold;">
                            <?php echo $sign . number_format($m['litros'], 2); ?>
                        </td>
                        <td>
                            <?php echo $m['detalle']; ?>
                        </td>
                        <td>
                            <?php echo $m['usuario']; ?>
                        </td>
                        <td>
                            <?php if (!empty($m['foto_ticket'])): ?>
                                <a href="../../<?php echo $m['foto_ticket']; ?>" target="_blank"
                                    class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-image"></i> Ver
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
require_once '../../includes/footer.php';
?>