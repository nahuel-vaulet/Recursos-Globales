<?php
// [!] ARQUITECTURA: Endpoint de movimientos de stock con seguridad
// [→] EDITAR AQUÍ: Rutas de inclusión si cambia la estructura
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDITORÍA CRUD: Verificación de sesión obligatoria
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../../includes/classes/StockMover.php';

    $mover = new StockMover($pdo);

    try {
        $result = $mover->processBatch($_POST);

        // Check if a remito was generated and redirect requested
        if (is_array($result) && isset($result['id_remito']) && !empty($_POST['redirect_to_remito'])) {
            header("Location: remito.php?id=" . $result['id_remito']);
        } else {
            header("Location: ../stock/index.php?msg=success_bulk");
        }
    } catch (Exception $e) {
        // Log Error?
        $error = urlencode($e->getMessage());
        header("Location: form.php?msg=error&details=$error");
    }
}

?>