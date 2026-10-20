<?php
require_once 'config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Obtener la primera empresa disponible sin asumir columnas como 'activo'
    $id_empresa = $pdo->query("SELECT id_empresa FROM empresas LIMIT 1")->fetchColumn();

    if (!$id_empresa) {
        // Si no existe ninguna empresa, creamos una básica
        $stmtEmp = $pdo->prepare("INSERT INTO empresas (razon_social, ruc) VALUES ('RUVI INDUSTRIAS S.A.C.', '20000000001')");
        $stmtEmp->execute();
        $id_empresa = $pdo->lastInsertId();
    }

    $email = 'admin@ruvindustrias.com';
    $password = 'admin123';
    $passHash = password_hash($password, PASSWORD_DEFAULT);

    // 2. Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $id_usuario = $user['id_usuario'];
        // Actualizar el usuario a ADMIN y asignarle la empresa
        $stmtUp = $pdo->prepare("UPDATE usuarios SET password_hash = :pass, rol = 'ADMIN', id_empresa = :id_empresa WHERE id_usuario = :id");
        $stmtUp->execute([
            ':pass'       => $passHash,
            ':id_empresa' => $id_empresa,
            ':id'         => $id_usuario
        ]);
        echo "<h3 style='color:green;'>¡Usuario actualizado a ADMIN con éxito!</h3>";
    } else {
        // Insertar un nuevo usuario administrador
        $stmtIns = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, email, password_hash, rol, id_empresa) VALUES ('Admin Ruvi', :email, :pass, 'ADMIN', :id_empresa)");
        $stmtIns->execute([
            ':email'      => $email,
            ':pass'       => $passHash,
            ':id_empresa' => $id_empresa
        ]);
        $id_usuario = $pdo->lastInsertId();
        echo "<h3 style='color:green;'>¡Usuario Admin Creado Exitosamente!</h3>";
    }

    // 3. Asignar TODOS los módulos existentes
    $modulos = $pdo->query("SELECT id_modulo FROM modulos")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($modulos)) {
        // Limpiar permisos antiguos para evitar duplicados
        $pdo->prepare("DELETE FROM usuario_permisos WHERE id_usuario = :id")->execute([':id' => $id_usuario]);

        $stmtPerm = $pdo->prepare("INSERT INTO usuario_permisos (id_usuario, id_modulo) VALUES (:id_user, :id_mod)");
        foreach ($modulos as $id_mod) {
            $stmtPerm->execute([':id_user' => $id_usuario, ':id_mod' => $id_mod]);
        }
    }

    $pdo->commit();

    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Contraseña:</strong> $password</p>";
    echo "<p><strong>ID Empresa asignada:</strong> $id_empresa</p>";
    echo "<p><a href='login.php'>Ir al Login de inmediato</a></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red;'>Error al crear admin: " . $e->getMessage() . "</h3>";
}