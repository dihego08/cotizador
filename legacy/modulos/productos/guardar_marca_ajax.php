<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_marca'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO marcas (nombre_marca) VALUES (:nombre)");
            $stmt->execute([':nombre' => $nombre]);

            echo json_encode([
                'status' => 'success',
                'nombre_marca' => $nombre
            ]);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(['status' => 'error', 'message' => 'La marca ya existe en el sistema.']);
                exit;
            }
            echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos.']);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca no puede estar vacío.']);
exit;