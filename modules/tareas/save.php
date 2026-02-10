<?php
require_once '../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_definicion = $_POST['id_definicion'] ?? null;
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $importancia = $_POST['importancia'];
    $tipo = $_POST['tipo_recurrencia'];
    $id_creador = $_SESSION['usuario_id'] ?? 1; // Fallback admin

    try {
        $parametro = null;
        $fecha_inicio = null;
        $fecha_fin = null;

        if ($tipo === 'Unica') {
            $fecha_inicio = $_POST['fecha_unica'];
            // Para tareas únicas, fecha_fin es igual a inicio para simplificar logica
            $fecha_fin = $fecha_inicio;
        } else {
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

            if ($tipo === 'Semanal')
                $parametro = $_POST['parametro_semanal'];
            if ($tipo === 'Mensual')
                $parametro = $_POST['parametro_mensual'];
        }

        if ($id_definicion) {
            // Update
            $sql = "UPDATE tareas_definicion SET titulo=?, descripcion=?, importancia=?, tipo_recurrencia=?, parametro_recurrencia=?, fecha_inicio=?, fecha_fin=? WHERE id_definicion=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titulo, $descripcion, $importancia, $tipo, $parametro, $fecha_inicio, $fecha_fin, $id_definicion]);
        } else {
            // Insert
            $sql = "INSERT INTO tareas_definicion (titulo, descripcion, importancia, tipo_recurrencia, parametro_recurrencia, fecha_inicio, fecha_fin, id_creador) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titulo, $descripcion, $importancia, $tipo, $parametro, $fecha_inicio, $fecha_fin, $id_creador]);

            // Si es Única, generamos la instancia YA MISMO
            if ($tipo === 'Unica') {
                $id_def = $pdo->lastInsertId();
                $sqlInst = "INSERT INTO tareas_instancia (id_definicion, titulo, descripcion, fecha_vencimiento, importancia) VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sqlInst)->execute([$id_def, $titulo, $descripcion, $fecha_inicio, $importancia]);

                // Marcar como generado
                $pdo->prepare("UPDATE tareas_definicion SET ultimo_generado = ? WHERE id_definicion = ?")->execute([$fecha_inicio, $id_def]);
            }
        }

        header("Location: index.php?msg=saved");
        exit;

    } catch (PDOException $e) {
        header("Location: form.php?msg=error&details=" . urlencode($e->getMessage()));
        exit;
    }
}
