<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPadre = (int)($_POST['id_padre'] ?? 0);
    $nombre  = trim($_POST['nombre'] ?? '');

    if ($idPadre === 0 || empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'Categoría padre y nombre son requeridos.']);
        exit;
    }

    try {
        $sql = "INSERT INTO helpdesk_categorias (id_padre, nombre, activo) VALUES (:id_padre, :nombre, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_padre' => $idPadre,
            ':nombre'   => $nombre
        ]);

        $idSubcategoria = $pdo->lastInsertId();

        echo json_encode([
            'status'          => 'success',
            'id_subcategoria' => $idSubcategoria,
            'nombre'          => $nombre
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);