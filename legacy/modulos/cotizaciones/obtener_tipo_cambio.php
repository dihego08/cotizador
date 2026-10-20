<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

$fecha = $_GET['fecha'] ?? date('Y-m-d');

try {
    // Busca el tipo de cambio de la fecha o el ÚLTIMO REGISTRADO ANTERIOR A ELLA
    $stmt = $pdo->prepare("SELECT * FROM tipo_cambio WHERE fecha <= :fecha ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([':fecha' => $fecha]);
    $tc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tc) {
        echo json_encode([
            'status' => 'success',
            'compra' => $tc['compra'],
            'venta'  => $tc['venta'],
            'fecha_registrada' => $tc['fecha']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No hay ningún tipo de cambio registrado en el sistema.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}