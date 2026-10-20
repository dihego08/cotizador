<?php
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_almacen = trim($_POST['nombre_almacen']);
    $ubicacion      = trim($_POST['ubicacion']);

    if (!empty($nombre_almacen)) {
        try {
            // Obtenemos el ID de la primera empresa registrada
            $stmtEmp = $pdo->query("SELECT id_empresa FROM empresas LIMIT 1");
            $empresa = $stmtEmp->fetch();

            if (!$empresa) {
                die("Error: No existe ninguna empresa creada. Ejecuta el SQL inicial de empresas.");
            }

            $sql = "INSERT INTO almacenes (id_empresa, nombre_almacen, ubicacion) VALUES (:id_empresa, :nombre, :ubicacion)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_empresa' => $empresa['id_empresa'],
                ':nombre'     => $nombre_almacen,
                ':ubicacion'  => $ubicacion
            ]);

        } catch (\PDOException $e) {
            die("Error al guardar el almacén: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;