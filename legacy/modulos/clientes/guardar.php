<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_documento      = $_POST['tipo_documento'] ?? 'RUC';
    $num_documento       = trim($_POST['num_documento'] ?? '');
    $razon_social        = trim($_POST['razon_social'] ?? '');
    $atencion            = !empty($_POST['atencion']) ? trim($_POST['atencion']) : null;
    $telefono            = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $email               = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $direccion           = !empty($_POST['direccion']) ? trim($_POST['direccion']) : null;
    $departamento        = !empty($_POST['departamento']) ? trim($_POST['departamento']) : null;
    $provincia           = !empty($_POST['provincia']) ? trim($_POST['provincia']) : null;
    $distrito            = !empty($_POST['distrito']) ? trim($_POST['distrito']) : null;
    $ubigeo              = !empty($_POST['ubigeo']) ? trim($_POST['ubigeo']) : null;
    $es_cliente_helpdesk = isset($_POST['es_cliente_helpdesk']) ? 1 : 0;

    if (!empty($num_documento) && !empty($razon_social)) {
        try {
            $sql = "INSERT INTO clientes (tipo_documento, num_documento, razon_social, atencion, telefono, email, direccion, departamento, provincia, distrito, ubigeo, es_cliente_helpdesk) 
                    VALUES (:tipo, :doc, :razon, :atencion, :tel, :email, :dir, :dep, :prov, :dist, :ubi, :hd)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo'     => $tipo_documento,
                ':doc'      => $num_documento,
                ':razon'    => $razon_social,
                ':atencion' => $atencion,
                ':tel'      => $telefono,
                ':email'    => $email,
                ':dir'      => $direccion,
                ':dep'      => $departamento,
                ':prov'     => $provincia,
                ':dist'     => $distrito,
                ':ubi'      => $ubigeo,
                ':hd'       => $es_cliente_helpdesk
            ]);

            header('Location: index.php?msg=created');
            exit;
        } catch (PDOException $e) {
            die("Error al guardar cliente: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;