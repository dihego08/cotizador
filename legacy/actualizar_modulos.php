<?php
require_once 'config/db.php';

// 1. Modificar estructura de la tabla 'modulos' (Fuera de transacción para evitar auto-commit en MySQL)
$columnas = [
    "id_padre INT NULL DEFAULT NULL AFTER id_modulo",
    "icono VARCHAR(50) NULL AFTER clave",
    "orden INT DEFAULT 0 AFTER icono"
];

foreach ($columnas as $col) {
    try {
        $pdo->exec("ALTER TABLE modulos ADD COLUMN $col");
    } catch (Exception $e) {
        // Ignorar si la columna ya existía
    }
}

// Intentar agregar la Clave Foránea
try {
    $pdo->exec("ALTER TABLE modulos ADD CONSTRAINT fk_modulos_padre FOREIGN KEY (id_padre) REFERENCES modulos(id_modulo) ON DELETE CASCADE");
} catch (Exception $e) {
    // Ignorar si la restricción ya existía
}


// 2. Insertar los datos en la base de datos dentro de una transacción limpia
try {
    $pdo->beginTransaction();

    // Limpiar datos antiguos
    $pdo->exec("DELETE FROM usuario_permisos");
    $pdo->exec("DELETE FROM modulos");

    // A. Insertar MENÚS PRINCIPALES (PADRES)
    $padres = [
        1 => ['nombre' => 'Operaciones',          'clave' => 'operaciones',          'icono' => 'fa-briefcase',       'orden' => 1],
        2 => ['nombre' => 'Almacén',              'clave' => 'almacen',              'icono' => 'fa-warehouse',       'orden' => 2],
        3 => ['nombre' => 'Finanzas',             'clave' => 'finanzas',             'icono' => 'fa-wallet',          'orden' => 3],
        4 => ['nombre' => 'Gestión Corporativa',  'clave' => 'gestion_corporativa',  'icono' => 'fa-chart-pie',       'orden' => 4],
        5 => ['nombre' => 'Admin Sistema',        'clave' => 'admin_sistema',        'icono' => 'fa-user-shield',     'orden' => 5],
    ];

    $stmtPadre = $pdo->prepare("INSERT INTO modulos (id_modulo, id_padre, nombre, clave, icono, orden) VALUES (:id, NULL, :nombre, :clave, :icono, :orden)");
    foreach ($padres as $id => $p) {
        $stmtPadre->execute([
            ':id'     => $id,
            ':nombre' => $p['nombre'],
            ':clave'  => $p['clave'],
            ':icono'  => $p['icono'],
            ':orden'  => $p['orden']
        ]);
    }

    // B. Insertar SUBMENÚS (HIJOS)
    $submodulos = [
        // Operaciones (padre: 1)
        ['id_padre' => 1, 'nombre' => 'Cotizaciones',              'clave' => 'cotizaciones',         'icono' => 'fa-file-invoice-dollar', 'orden' => 1],
        ['id_padre' => 1, 'nombre' => 'Clientes',                  'clave' => 'clientes',             'icono' => 'fa-users',               'orden' => 2],

        // Almacén (padre: 2)
        ['id_padre' => 2, 'nombre' => 'Productos y Servicios',    'clave' => 'productos',            'icono' => 'fa-boxes-stacked',       'orden' => 1],
        ['id_padre' => 2, 'nombre' => 'Categorías y Marcas',       'clave' => 'categorias_marcas',    'icono' => 'fa-layer-group',         'orden' => 2],
        ['id_padre' => 2, 'nombre' => 'Control de Stock',          'clave' => 'stock',                'icono' => 'fa-chart-simple',        'orden' => 3],
        ['id_padre' => 2, 'nombre' => 'Movimientos',               'clave' => 'movimientos',          'icono' => 'fa-arrow-right-left',    'orden' => 4],

        // Finanzas (padre: 3)
        ['id_padre' => 3, 'nombre' => 'Tipo de Cambio',            'clave' => 'tipo_cambio',          'icono' => 'fa-dollar-sign',         'orden' => 1],

        // Gestión Corporativa (padre: 4)
        ['id_padre' => 4, 'nombre' => 'Gerencia y Dirección',     'clave' => 'corp_gerencia',        'icono' => 'fa-user-tie',            'orden' => 1],
        ['id_padre' => 4, 'nombre' => 'Control de Gestión',       'clave' => 'corp_control',         'icono' => 'fa-shield-halved',       'orden' => 2],
        ['id_padre' => 4, 'nombre' => 'Administración General',    'clave' => 'corp_admin',           'icono' => 'fa-file-invoice',        'orden' => 3],

        // Admin Sistema (padre: 5)
        ['id_padre' => 5, 'nombre' => 'Gestión de Usuarios',       'clave' => 'usuarios',             'icono' => 'fa-users-gear',          'orden' => 1],
        ['id_padre' => 5, 'nombre' => 'Gestión de Empresas',       'clave' => 'empresas',             'icono' => 'fa-building',            'orden' => 2],
    ];

    $stmtHijo = $pdo->prepare("INSERT INTO modulos (id_padre, nombre, clave, icono, orden) VALUES (:id_padre, :nombre, :clave, :icono, :orden)");
    foreach ($submodulos as $sub) {
        $stmtHijo->execute([
            ':id_padre' => $sub['id_padre'],
            ':nombre'   => $sub['nombre'],
            ':clave'    => $sub['clave'],
            ':icono'    => $sub['icono'],
            ':orden'    => $sub['orden']
        ]);
    }

    // C. Asignar automáticamentes TODOS los permisos al usuario Administrador
    $adminId = $pdo->query("SELECT id_usuario FROM usuarios WHERE rol = 'ADMIN' LIMIT 1")->fetchColumn();
    if (!$adminId) {
        $adminId = $pdo->query("SELECT id_usuario FROM usuarios LIMIT 1")->fetchColumn();
    }

    if ($adminId) {
        $todosModulos = $pdo->query("SELECT id_modulo FROM modulos")->fetchAll(PDO::FETCH_COLUMN);
        $stmtPerm = $pdo->prepare("INSERT INTO usuario_permisos (id_usuario, id_modulo) VALUES (:u, :m)");
        foreach ($todosModulos as $idMod) {
            $stmtPerm->execute([':u' => $adminId, ':m' => $idMod]);
        }
    }

    $pdo->commit();

    echo "<h2 style='color:green; font-family:sans-serif;'>¡Módulos reestructurados correctamente con 'Gestión Corporativa'!</h2>";
    echo "<p style='font-family:sans-serif;'><a href='modulos/usuarios/editar.php?id=$adminId'>Clic aquí para volver al módulo de usuarios</a></p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2 style='color:red; font-family:sans-serif;'>Error al actualizar módulos: " . $e->getMessage() . "</h2>";
}