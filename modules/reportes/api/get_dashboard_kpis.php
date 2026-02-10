<?php
require_once '../../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
        $response = [
                'certification' => 0,
                'certification_raw' => 0,
                'certification_percent' => 0,
                'stock_alerts' => 0,
                'purchase_alerts' => 0,
                'expiration_alerts' => 0,
                'urgent_tasks' => [],
                'pending_tasks' => []
        ];

        // 1. Certification (Certificación Proyectada)
        // Lógica: Sumatoria valor ODTs 'Finalizado' en mes en curso (por fecha_vencimiento)
        $meta_mensual = 20000000; // Meta hardcodeada: 20 Millones (Ajustable)

        $sql = "SELECT SUM(t.precio_unitario) as total 
            FROM odt_maestro o 
            JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia 
            WHERE o.estado_gestion = 'Finalizado' 
            AND MONTH(o.fecha_vencimiento) = MONTH(CURRENT_DATE()) 
            AND YEAR(o.fecha_vencimiento) = YEAR(CURRENT_DATE())";

        $stmt = $pdo->query($sql);
        $current_cert = 0;
        if ($stmt) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $current_cert = $row['total'] ?? 0;
        }

        $response['certification'] = number_format($current_cert, 2, ',', '.');
        $response['certification_raw'] = (float) $current_cert;
        $response['certification_percent'] = ($meta_mensual > 0) ? round(($current_cert / $meta_mensual) * 100) : 0;

        // 2. Stock Alert (Inventario Crítico)
        // Lógica: stock_oficina < punto_pedido
        $sql = "SELECT COUNT(*) as cnt FROM stock_saldos s 
            JOIN maestro_materiales m ON s.id_material = m.id_material 
            WHERE s.stock_oficina < m.punto_pedido";
        $stmt = $pdo->query($sql);
        $response['stock_alerts'] = $stmt->fetchColumn();

        // 3. Purchase Alert (Compras Pendientes de Ingreso)
        // Lógica: 'Compra_Material' sin usuario_recepcion (Confirmación física)
        $sql = "SELECT COUNT(*) as cnt FROM movimientos 
            WHERE tipo_movimiento = 'Compra_Material' 
            AND (usuario_recepcion IS NULL OR usuario_recepcion = '')";
        $stmt = $pdo->query($sql);
        $response['purchase_alerts'] = $stmt->fetchColumn();

        // 4. Expiration Alert (Vencimientos < 15 días)
        // Vehículos (VTV, Seguro) y Personal (Carnet de Conducir)
        $days = 15;

        // Check Vehículos
        $sql_veh = "SELECT COUNT(*) FROM vehiculos 
                WHERE (vencimiento_vtv IS NOT NULL AND vencimiento_vtv < DATE_ADD(CURDATE(), INTERVAL $days DAY) AND vencimiento_vtv >= CURDATE())
                OR (vencimiento_seguro IS NOT NULL AND vencimiento_seguro < DATE_ADD(CURDATE(), INTERVAL $days DAY) AND vencimiento_seguro >= CURDATE())";
        $veh_alerts = $pdo->query($sql_veh)->fetchColumn();

        // Check Personal
        $sql_pers = "SELECT COUNT(*) FROM personal 
                 WHERE vencimiento_carnet_conducir IS NOT NULL 
                 AND vencimiento_carnet_conducir < DATE_ADD(CURDATE(), INTERVAL $days DAY) 
                 AND vencimiento_carnet_conducir >= CURDATE()";
        $pers_alerts = $pdo->query($sql_pers)->fetchColumn();

        $response['expiration_alerts'] = $veh_alerts + $pers_alerts;

        // 0. Auto-generar Tareas Recurrentes si es necesario
        require_once __DIR__ . '/../../../includes/tasks_generator.php'; // Asegurar ruta correcta
        generarTareasPendientes($pdo);

        // 5. Panel A: Urgent Tasks (Hoy)
        // UNION: ODTs Urgentes (Programadas Hoy) + Tareas Instancia Urgentes (No completadas y Vencidas/Hoy)

        // Parte 1: ODTs (Programadas para Hoy o Mañana)
        $sql_odt = "SELECT 
                    o.id_odt as id, 
                    CONCAT('ODT ', o.nro_odt_assa) as titulo, 
                    o.direccion as descripcion, 
                    t.nombre as tipo, 
                    'ODT' as source,
                    CASE WHEN o.prioridad = 'Urgente' THEN 1 ELSE 0 END as is_urgent,
                    o.estado_gestion as estado
                FROM odt_maestro o 
                LEFT JOIN programacion_semanal p ON o.id_odt = p.id_odt 
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia 
                WHERE o.estado_gestion = 'Programado' 
                AND p.fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

        // Parte 2: Tareas de Agenda (Vencen Hoy o Mañana)
        $sql_tasks = "SELECT 
                    id_tarea as id,
                    titulo,
                    descripcion,
                    'Tarea' as tipo,
                    'AGENDA' as source,
                    CASE WHEN importancia = 'Alta' THEN 1 ELSE 0 END as is_urgent,
                    estado
                  FROM tareas_instancia
                  WHERE estado IN ('Pendiente', 'En Curso')
                  AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

        // Parte 3: Personal con Documentación Pendiente (ALTA PRIORIDAD)
        $sql_personal_pendiente = "SELECT 
                    id_personal as id,
                    CONCAT('LEGAJO: ', nombre_apellido) as titulo,
                    CONCAT('Motivo: ', COALESCE(motivo_pendiente, 'Falta documentación')) as descripcion,
                    'Documentación' as tipo,
                    'PERSONAL' as source,
                    1 as is_urgent,
                    estado_documentacion as estado
                  FROM personal
                  WHERE estado_documentacion IN ('Incompleto', 'Pendiente')";

        // Ordenar: Primero los marcados explicitamente como urgentes (visual), luego por ID
        // Union de 3 partes: ODTs + Tareas + Personal Pendiente
        $sql_union = "($sql_personal_pendiente) UNION ALL ($sql_odt) UNION ALL ($sql_tasks) ORDER BY is_urgent DESC, id ASC";
        $response['urgent_tasks'] = $pdo->query($sql_union)->fetchAll(PDO::FETCH_ASSOC);

        // 6. Panel B: Pending Tray (Sin Programar / Tareas Próximas)
        // UNION: ODTs Sin Programar + Tareas Pendientes (Normales)

        $sql_odt_pending = "SELECT 
                            o.id_odt as id, 
                            CONCAT('ODT ', o.nro_odt_assa) as titulo, 
                            o.fecha_vencimiento as fecha, 
                            t.nombre as tipo, 
                            o.prioridad,
                            'ODT' as source
                        FROM odt_maestro o 
                        LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia 
                        WHERE o.estado_gestion = 'Sin Programar'";

        $sql_tasks_pending = "SELECT 
                            id_tarea as id,
                            titulo,
                            fecha_vencimiento as fecha,
                            'Tarea' as tipo,
                            importancia as prioridad,
                            'AGENDA' as source
                          FROM tareas_instancia
                          WHERE estado IN ('Pendiente', 'En Curso')
                          AND (importancia != 'Alta' OR fecha_vencimiento > CURDATE())";

        $sql_union_pending = "($sql_odt_pending) UNION ALL ($sql_tasks_pending) ORDER BY fecha ASC LIMIT 10";
        $response['pending_tasks'] = $pdo->query($sql_union_pending)->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($response);

} catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
}
