<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$tipo      = $_GET['tipo'] ?? '';
$documento = trim($_GET['documento'] ?? '');

if (empty($tipo) || empty($documento)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo y número de documento requeridos.']);
    exit;
}

if ($tipo === 'DNI' && strlen($documento) === 8) {
    $url = 'https://api.apis.net.pe/v1/dni?numero=' . $documento;
} elseif ($tipo === 'RUC' && strlen($documento) === 11) {
    $url = 'https://api.apis.net.pe/v1/ruc?numero=' . $documento;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Longitud de documento inválida.']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    
    if ($tipo === 'DNI') {
        $nombreCompleto = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? ''));
        if (!empty($nombreCompleto)) {
            echo json_encode([
                'status'       => 'success',
                'tipo'         => 'DNI',
                'razon_social' => $nombreCompleto,
                'direccion'    => '',
                'departamento' => '',
                'provincia'    => '',
                'distrito'     => '',
                'ubigeo'       => ''
            ]);
            exit;
        }
    } else {
        $razonSocial = $data['nombre'] ?? $data['razonSocial'] ?? '';
        if (!empty($razonSocial)) {
            echo json_encode([
                'status'       => 'success',
                'tipo'         => 'RUC',
                'razon_social' => $razonSocial,
                'direccion'    => $data['direccion'] ?? '',
                'departamento' => $data['departamento'] ?? '',
                'provincia'    => $data['provincia'] ?? '',
                'distrito'     => $data['distrito'] ?? '',
                'ubigeo'       => $data['ubigeo'] ?? ''
            ]);
            exit;
        }
    }
}

echo json_encode([
    'status'  => 'error', 
    'message' => 'No se encontraron datos en SUNAT/RENIEC.'
]);
exit;