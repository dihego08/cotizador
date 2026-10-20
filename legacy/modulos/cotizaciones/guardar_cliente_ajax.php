<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_documento = trim($_POST['tipo_documento'] ?? 'RUC');
    $num_documento  = trim($_POST['num_documento'] ?? '');
    $razon_social   = trim($_POST['razon_social'] ?? '');
    $direccion      = trim($_POST['direccion'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $telefono       = trim($_POST['telefono'] ?? '');
    $atencion       = trim($_POST['atencion'] ?? '');

    if (empty($num_documento) || empty($razon_social)) {
        echo json_encode(['status' => 'error', 'message' => 'El documento y la razón social son obligatorios.']);
        exit;
    }

    try {
        // Verificar si el documento ya está registrado
        $stmtCheck = $pdo->prepare("SELECT id_cliente FROM clientes WHERE num_documento = :num_doc LIMIT 1");
        $stmtCheck->execute([':num_doc' => $num_documento]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El número de documento ya se encuentra registrado.']);
            exit;
        }

        // Insertar nuevo cliente
        $sql = "INSERT INTO clientes (tipo_documento, num_documento, razon_social, direccion, email, telefono, atencion) 
                VALUES (:tipo, :num, :razon, :dir, :email, :tel, :atencion)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo'     => $tipo_documento,
            ':num'      => $num_documento,
            ':razon'    => $razon_social,
            ':dir'      => $direccion,
            ':email'    => $email,
            ':tel'      => $telefono,
            ':atencion' => $atencion
        ]);

        $id_nuevo = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'cliente' => [
                'id_cliente'    => $id_nuevo,
                'num_documento' => $num_documento,
                'razon_social'  => $razon_social,
                'direccion'     => $direccion,
                'email'         => $email,
                'atencion'      => $atencion
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
    }
}