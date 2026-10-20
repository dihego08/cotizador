<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_documento = trim($_POST['num_documento'] ?? '');
    $razon_social  = trim($_POST['razon_social'] ?? '');
    $atencion      = !empty($_POST['atencion']) ? trim($_POST['atencion']) : null;
    $telefono      = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $email         = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $direccion     = !empty($_POST['direccion']) ? trim($_POST['direccion']) : null;

    if (empty($num_documento) || empty($razon_social)) {
        echo json_encode(['status' => 'error', 'message' => 'RUC/DNI y Razón Social son requeridos.']);
        exit;
    }

    try {
        // Verificar si ya existe el número de documento
        $check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE num_documento = :doc");
        $check->execute([':doc' => $num_documento]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El número de documento ya está registrado.']);
            exit;
        }

        $sql = "INSERT INTO clientes (num_documento, razon_social, atencion, telefono, email, direccion) 
                VALUES (:doc, :razon, :atencion, :tel, :email, :dir)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':doc'      => $num_documento,
            ':razon'    => $razon_social,
            ':atencion' => $atencion,
            ':tel'      => $telefono,
            ':email'    => $email,
            ':dir'      => $direccion
        ]);

        $id_cliente = $pdo->lastInsertId();

        echo json_encode([
            'status'        => 'success',
            'id_cliente'    => $id_cliente,
            'num_documento' => $num_documento,
            'razon_social'  => $razon_social,
            'atencion'      => $atencion,
            'direccion'     => $direccion,
            'email'         => $email
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error BD: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);