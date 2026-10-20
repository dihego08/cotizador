<?php
require_once 'config/db.php';

$columnas = [
    "cargo VARCHAR(100) NULL",
    "celular VARCHAR(30) NULL",
    "firma_path VARCHAR(255) NULL"
];

foreach ($columnas as $col) {
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN $col");
    } catch (Exception $e) {
        // Ignora el error si la columna ya existía
    }
}

echo "<h3 style='color:green;'>¡Estructura de la tabla usuarios actualizada correctamente!</h3>";
echo "<p><a href='modulos/usuarios/index.php'>Volver al módulo de usuarios</a></p>";