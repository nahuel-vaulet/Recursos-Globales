<?php
// 2. Fetch Active Squads Meta Data (Name, Vehicle)
$sql_squads_meta = "SELECT c.id_cuadrilla, c.nombre_cuadrilla, c.id_vehiculo_asignado, 
                           v.marca, v.modelo, v.patente
                    FROM cuadrillas c
                    LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id_vehiculo
                    WHERE c.estado_operativo = 'Activa'
                    ORDER BY c.nombre_cuadrilla";
$squads_meta = $pdo->query($sql_squads_meta)->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Today's Fuel Consumption Grouped by Vehicle
// We sum both Dispatches (from Tank) AND Direct Purchases (combustibles_cargas to vehicle)
$today = date('Y-m-d');
$sql_fuel = "
    SELECT id_vehiculo, SUM(litros) as litros_hoy FROM (
        SELECT id_vehiculo, litros FROM combustibles_despachos WHERE DATE(fecha_hora) = '$today' AND id_vehiculo IS NOT NULL
        UNION ALL
        SELECT id_vehiculo, litros FROM combustibles_cargas WHERE DATE(fecha_hora) = '$today' AND destino_tipo = 'vehiculo' AND id_vehiculo IS NOT NULL
    ) as combined_fuel
    GROUP BY id_vehiculo";

$fuel_data = $pdo->query($sql_fuel)->fetchAll(PDO::FETCH_KEY_PAIR); // [id_vehiculo => litros]

// 4. Fetch Stock Items for Squads
$sql_squads_items = "SELECT sc.id_cuadrilla, m.nombre as material, sc.cantidad, m.unidad_medida
                     FROM stock_cuadrilla sc
                     JOIN maestro_materiales m ON sc.id_material = m.id_material
                     WHERE sc.cantidad > 0
                     ORDER BY m.nombre";
$raw_squad_items = $pdo->query($sql_squads_items)->fetchAll(PDO::FETCH_ASSOC);

// 5. Build Final Data Structure
$squads_data = [];
// Initialize with meta
foreach ($squads_meta as $meta) {
    $id = $meta['id_cuadrilla'];
    $vid = $meta['id_vehiculo_asignado'];

    $squads_data[$id] = [
        'name' => $meta['nombre_cuadrilla'],
        'vehicle' => $meta['patente'] ? ($meta['marca'] . ' ' . $meta['patente']) : null,
        'fuel_today' => ($vid && isset($fuel_data[$vid])) ? $fuel_data[$vid] : 0,
        'items' => []
    ];
}

// Attach items
foreach ($raw_squad_items as $item) {
    $id = $item['id_cuadrilla'];
    if (isset($squads_data[$id])) {
        $squads_data[$id]['items'][] = $item;
    }
}
?>