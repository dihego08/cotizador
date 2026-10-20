<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// 1. DESCARGAR PLANTILLA CSV DE EJEMPLO
// --------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'descargar_plantilla') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=plantilla_importacion_productos.csv');

    // Salida con UTF-8 BOM para soporte correcto de tildes y caracteres en Excel
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados
    fputcsv($output, [
        'tipo',
        'codigo_interno',
        'codigo_proveedor',
        'nombre_producto',
        'descripcion',
        'numero_parte',
        'marca',
        'modelo',
        'unidad_medida',
        'moneda',
        'precio_costo',
        'porcentaje_utilidad',
        'precio_venta',
        'codigo_barra'
    ]);

    // Fila de ejemplo
    fputcsv($output, [
        'PRODUCTO',
        'PROD-001',
        'PROV-101',
        'Cable de Red UTP Categoria 6',
        'Cable de red 100% cobre bobina 305m',
        'UTP-CAT6-305M',
        'Cisco',
        'Cat6 Standard',
        'UND',
        'PEN',
        '120.00',
        '20.00',
        '144.00',
        '775000112233'
    ]);

    fclose($output);
    exit;
}

// --------------------------------------------------------------------------
// 2. PROCESAR SUBIDA E IMPORTACIÓN DEL ARCHIVO CSV
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    try {
        if ($_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo.");
        }

        $fileName = $_FILES['archivo_csv']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            throw new Exception("Formato no válido. Debe subir un archivo con extensión .csv");
        }

        $fileTmp = $_FILES['archivo_csv']['tmp_filename'] ?? $_FILES['archivo_csv']['tmp_name'];
        $handle = fopen($fileTmp, 'r');

        if (!$handle) {
            throw new Exception("No se pudo abrir el archivo CSV.");
        }

        // Detectar BOM UTF-8 y omitirlo si existe
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Leer primera fila (Encabezados)
        $encabezados = fgetcsv($handle, 1000, ",");
        
        // Si no se separó por coma, probar con punto y coma (;)
        if (count($encabezados) === 1) {
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
            $encabezados = fgetcsv($handle, 1000, ";");
            $delimitador = ";";
        } else {
            $delimitador = ",";
        }

        $pdo->beginTransaction();

        $sql = "INSERT INTO productos (
                    tipo, codigo_interno, codigo_proveedor, nombre_producto, descripcion, 
                    numero_parte, marca, modelo, unidad_medida, moneda, 
                    precio_costo, porcentaje_utilidad, precio_venta, codigo_barra
                ) VALUES (
                    :tipo, :codigo_interno, :codigo_proveedor, :nombre_producto, :descripcion, 
                    :numero_parte, :marca, :modelo, :unidad_medida, :moneda, 
                    :precio_costo, :porcentaje_utilidad, :precio_venta, :codigo_barra
                ) ON DUPLICATE KEY UPDATE 
                    nombre_producto = VALUES(nombre_producto),
                    precio_costo = VALUES(precio_costo),
                    porcentaje_utilidad = VALUES(porcentaje_utilidad),
                    precio_venta = VALUES(precio_venta),
                    marca = VALUES(marca)";

        $stmt = $pdo->prepare($sql);

        $insertados = 0;
        $fila = 1;

        while (($data = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
            $fila++;
            if (empty(array_filter($data))) continue; // Omitir filas vacías

            $tipo          = !empty($data[0]) ? strtoupper(trim($data[0])) : 'PRODUCTO';
            $codInterno    = trim($data[1] ?? '');
            $codProveedor  = trim($data[2] ?? '');
            $nombreProd    = trim($data[3] ?? '');
            $descripcion   = trim($data[4] ?? '');
            $numParte      = trim($data[5] ?? '');
            $marca         = trim($data[6] ?? '');
            $modelo        = trim($data[7] ?? '');
            $unidMedida    = !empty($data[8]) ? trim($data[8]) : 'UND';
            $moneda        = !empty($data[9]) ? strtoupper(trim($data[9])) : 'PEN';
            $costo         = floatval($data[10] ?? 0);
            $utilidad      = floatval($data[11] ?? 0);
            $precioVenta   = floatval($data[12] ?? 0);
            $codBarra      = trim($data[13] ?? '');

            if (empty($codInterno) || empty($nombreProd)) {
                continue; // Código interno y nombre son obligatorios
            }

            // Calcular precio de venta si viene en cero pero hay costo y utilidad
            if ($precioVenta == 0 && $costo > 0 && $utilidad > 0) {
                $precioVenta = $costo / (1 - ($utilidad / 100));
            }

            $stmt->execute([
                ':tipo'                => in_array($tipo, ['PRODUCTO', 'SERVICIO']) ? $tipo : 'PRODUCTO',
                ':codigo_interno'      => $codInterno,
                ':codigo_proveedor'    => $codProveedor,
                ':nombre_producto'     => $nombreProd,
                ':descripcion'         => $descripcion,
                ':numero_parte'        => $numParte,
                ':marca'               => $marca,
                ':modelo'              => $modelo,
                ':unidad_medida'       => $unidMedida,
                ':moneda'              => in_array($moneda, ['PEN', 'USD']) ? $moneda : 'PEN',
                ':precio_costo'        => $costo,
                ':porcentaje_utilidad' => $utilidad,
                ':precio_venta'        => $precioVenta,
                ':codigo_barra'        => $codBarra
            ]);

            $insertados++;
        }

        fclose($handle);
        $pdo->commit();

        $_SESSION['alert'] = [
            'type'    => 'success',
            'title'   => '¡Importación Exitosal!',
            'message' => "Se procesaron y registraron correctamente {$insertados} productos."
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Error en la importación',
            'message' => $e->getMessage()
        ];
    }

    header("Location: index.php");
    exit;
}