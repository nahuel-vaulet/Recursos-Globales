<?php
require_once 'config/database.php';
header('Content-Type: text/plain');

try {
    echo "Testing Certification Metric...\n";

    // Replicating the logic from get_dashboard_kpis.php
    $sql = "SELECT SUM(t.precio_unitario) as total 
            FROM odt_maestro o 
            JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia 
            WHERE o.estado_gestion = 'Finalizado' 
            AND MONTH(o.fecha_vencimiento) = MONTH(CURRENT_DATE()) 
            AND YEAR(o.fecha_vencimiento) = YEAR(CURRENT_DATE())";

    echo "Query being executed:\n$sql\n\n";

    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Result of fetch:\n";
    var_dump($row);

    echo "\nLogic check:\n";
    if ($row && isset($row['total'])) {
        echo "Row exists and total is set.\n";
    } else {
        echo "Row missing or total not set.\n";
    }

    $current_cert = $row['total'] ?? 0;
    echo "Current Cert (raw): ";
    var_dump($current_cert);

    $formatted = number_format($current_cert, 2, ',', '.');
    echo "Formatted: " . $formatted . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
