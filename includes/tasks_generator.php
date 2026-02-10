<?php
// includes/tasks_generator.php
require_once __DIR__ . '/../config/database.php';
// Esta lógica debe correrse al cargar el módulo o vía cron
// Genera instancias futuras basadas en definiciones

function generarTareasPendientes($pdo)
{
    $hoy = date('Y-m-d');

    // Buscar definiciones activas que necesitan generación
    // 1. Recurrentes (Diaria, Semanal, Mensual)
    // 2. Que no tengan fecha_fin o fecha_fin >= hoy

    $sql = "SELECT * FROM tareas_definicion 
            WHERE tipo_recurrencia != 'Unica' 
            AND (fecha_fin IS NULL OR fecha_fin >= ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hoy]);
    $defs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($defs as $d) {
        $ultimo = $d['ultimo_generado'] ? $d['ultimo_generado'] : date('Y-m-d', strtotime($d['fecha_inicio'] . ' -1 day'));
        $proxima = null;

        // Calcular próxima fecha teórica
        if ($d['tipo_recurrencia'] === 'Diaria') {
            $proxima = date('Y-m-d', strtotime($ultimo . ' +1 day'));
        } elseif ($d['tipo_recurrencia'] === 'Semanal') {
            // Parametro es 1 (Lunes) a 7 (Domingo). PHP date 'N' da 1 (Mon) a 7 (Sun)
            $diaObjetivo = $d['parametro_recurrencia'];
            // Avanzar dias hasta encontrar el próximo día objetivo
            for ($i = 1; $i <= 7; $i++) {
                $check = date('Y-m-d', strtotime($ultimo . " +$i day"));
                if (date('N', strtotime($check)) == $diaObjetivo) {
                    $proxima = $check;
                    break;
                }
            }
        } elseif ($d['tipo_recurrencia'] === 'Mensual') {
            $diaMes = $d['parametro_recurrencia']; // 1 a 31
            // Mes siguiente
            $mesSiguiente = date('Y-m', strtotime($ultimo . ' +1 month'));
            // Intentar crear fecha
            $testDate = $mesSiguiente . '-' . str_pad($diaMes, 2, '0', STR_PAD_LEFT);
            // Validar si es fecha válida (ej: 31 feb no existe)
            // Si no es válida, tomar el último día del mes
            $ultimoDiaMes = date('t', strtotime($mesSiguiente . '-01'));
            if ($diaMes > $ultimoDiaMes) {
                $testDate = $mesSiguiente . '-' . $ultimoDiaMes;
            }
            $proxima = $testDate;
        }

        // Si proxima <= hoy + 7 días (Pre-generamos una semana)
        if ($proxima && $proxima <= date('Y-m-d', strtotime($hoy . ' +7 days'))) {
            // Verificar que no sea anterior a fecha_inicio
            if ($proxima < $d['fecha_inicio'])
                continue;

            // Verificar si ya existe (Doble check)
            $checkSql = "SELECT id_tarea FROM tareas_instancia WHERE id_definicion = ? AND fecha_vencimiento = ?";
            $chk = $pdo->prepare($checkSql);
            $chk->execute([$d['id_definicion'], $proxima]);

            if (!$chk->fetch()) {
                // Insertar Instancia
                $ins = $pdo->prepare("INSERT INTO tareas_instancia (id_definicion, titulo, descripcion, fecha_vencimiento, importancia) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$d['id_definicion'], $d['titulo'], $d['descripcion'], $proxima, $d['importancia']]);

                // Actualizar ultimo generado
                $upd = $pdo->prepare("UPDATE tareas_definicion SET ultimo_generado = ? WHERE id_definicion = ?");
                $upd->execute([$proxima, $d['id_definicion']]);
            }
        }
    }
}
