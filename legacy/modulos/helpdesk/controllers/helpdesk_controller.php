<?php
/**
 * Controlador del Módulo Help Desk
 * Ruta: modulos/helpdesk/controllers/helpdesk_controller.php
 */

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión y requerir la conexión a la Base de Datos
session_start();

// Inclusión segura de la conexión a BD y del modelo
require_once __DIR__ . '/../../../config/db.php'; // Ajusta si tu archivo global se llama conexion.php
require_once __DIR__ . '/../models/helpdesk_model.php';

// Validar la instancia de conexión PDO ($pdo o $conexion)
$conexionBD = $pdo ?? $conexion ?? null;

if (!$conexionBD) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: No se encontró la variable de conexión.']);
    exit;
}

// Instanciar el modelo
try {
    $model = new HelpdeskModel($conexionBD);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al instanciar el modelo: ' . $e->getMessage()]);
    exit;
}

// Determinar la acción enviada
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // -------------------------------------------------------------------------
    // ACCIÓN 1: VALIDAR DNI EN LISTA DE CONTACTOS AUTORIZADOS
    // -------------------------------------------------------------------------
    case 'validar_contacto':
        $idCliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
        $documento = isset($_GET['documento']) ? trim($_GET['documento']) : '';

        if ($idCliente > 0 && !empty($documento)) {
            $contacto = $model->buscarContactoPorDoc($idCliente, $documento);

            if ($contacto) {
                echo json_encode([
                    'success' => true,
                    'contacto' => $contacto
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Contacto no registrado'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
        }
        break;

    // -------------------------------------------------------------------------
    // ACCIÓN 2: GUARDAR ATENCIÓN / TICKET CON TIEMPO DEL CONTÓMETRO
    // -------------------------------------------------------------------------
    case 'guardar_atencion':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Tomar la empresa activa de la sesión
            $idEmpresaSesion = $_SESSION['id_empresa'] ?? 2; // Por defecto id_empresa = 2

            $datos = [
                'id_empresa'               => $idEmpresaSesion,
                'id_cliente'               => $_POST['id_cliente'] ?? 0,
                'id_contacto'              => !empty($_POST['id_contacto']) ? $_POST['id_contacto'] : null,
                'tipo_usuario'             => $_POST['tipo_usuario'] ?? 'CORPORATIVO',
                'documento_solicitante'    => trim($_POST['documento_solicitante'] ?? ''),
                'nombre_solicitante'       => trim($_POST['nombre_solicitante'] ?? ''),
                'categoria'                => $_POST['categoria'] ?? 0,
                'id_subcategoria'          => $_POST['id_subcategoria'] ?? null, // AJUSTE: Captura de la subcategoría
                'canal_atencion'           => $_POST['canal_atencion'] ?? 'LLAMADA',
                'id_tecnico'               => $_POST['id_tecnico'] ?? ($_SESSION['id_usuario'] ?? 4),
                'descripcion_requerimiento'=> trim($_POST['descripcion_requerimiento'] ?? ''),
                'acciones_realizadas'      => trim($_POST['acciones_realizadas'] ?? ''),
                'estado_cierre'            => $_POST['estado_cierre'] ?? 'CONCLUIDO',
                'nivel_satisfaccion'       => $_POST['nivel_satisfaccion'] ?? null,
                'acciones_programadas'     => $_POST['acciones_programadas'] ?? null,
                'fecha_proxima_revision'   => $_POST['fecha_proxima_revision'] ?? null,
                'id_tecnico_seguimiento'   => $_POST['id_tecnico_seguimiento'] ?? null,
                'hora_inicio'              => $_POST['hora_inicio'] ?? date('Y-m-d H:i:s'),
                'hora_fin'                 => $_POST['hora_fin'] ?? date('Y-m-d H:i:s'),
                'tiempo_pausa_segundos'    => $_POST['tiempo_pausa_segundos'] ?? 0,
                'tiempo_efectivo_segundos' => $_POST['tiempo_efectivo_segundos'] ?? 0
            ];

            // Validaciones básicas de campos requeridos
            if (empty($datos['id_cliente']) || empty($datos['documento_solicitante']) || empty($datos['descripcion_requerimiento'])) {
                echo json_encode(['success' => false, 'message' => 'Por favor complete todos los campos obligatorios.']);
                exit;
            }

            // Ejecutar guardado en BD
            $resultado = $model->guardarAtencion($datos);

            if ($resultado['success']) {
                echo json_encode([
                    'success' => true,
                    'codigo_ticket' => $resultado['codigo_ticket'],
                    'message' => 'Atención registrada exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $resultado['message'] ?? 'No se pudo guardar el registro en la base de datos'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
        break;

    // -------------------------------------------------------------------------
    // ACCIÓN 3: OBTENER SUBCATEGORÍAS POR CATEGORÍA PADRE (AJAX)
    // -------------------------------------------------------------------------
    case 'obtener_subcategorias':
        $idPadre = isset($_GET['id_padre']) ? (int)$_GET['id_padre'] : 0;

        if ($idPadre > 0) {
            $sql = "SELECT id_categoria, nombre 
                    FROM helpdesk_categorias 
                    WHERE id_padre = :id_padre AND activo = 1 
                    ORDER BY nombre ASC";
            
            $stmt = $conexionBD->prepare($sql);
            $stmt->bindParam(':id_padre', $idPadre, PDO::PARAM_INT);
            $stmt->execute();
            $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'subcategorias' => $subcategorias
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'subcategorias' => []
            ]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}