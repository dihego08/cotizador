<?php
/**
 * Modelo de Datos del Módulo Help Desk
 * Ruta: modulos/helpdesk/models/helpdesk_model.php
 */

class HelpdeskModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    /**
     * Genera un código correlativo único para el ticket (Ej: HD-2026-0001)
     */
    public function generarCodigoTicket() {
        $anio = date('Y');
        $prefix = "HD-" . $anio . "-";

        $sql = "SELECT codigo_ticket FROM helpdesk_atenciones 
                WHERE codigo_ticket LIKE :prefix 
                ORDER BY id_atencion DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $ultimoNum = (int) substr($row['codigo_ticket'], -4);
            $nuevoNum = str_pad($ultimoNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nuevoNum = "0001";
        }

        return $prefix . $nuevoNum;
    }

    /**
     * Busca y valida si un documento (DNI/CE) pertenece a los contactos autorizados de un cliente
     */
    public function buscarContactoPorDoc($idCliente, $documento) {
        $sql = "SELECT id_contacto, nombres_apellidos, cargo, email, telefono 
                FROM cliente_contactos 
                WHERE id_cliente = :id_cliente 
                  AND num_documento = :documento 
                  AND activo = 1 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_cliente', (int)$idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':documento', $documento, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra una nueva atención de Help Desk con sus métricas de tiempo
     */
    public function guardarAtencion($datos) {
        try {
            $codigoTicket = $this->generarCodigoTicket();
            $fechaAtencion = date('Y-m-d');

            $sql = "INSERT INTO helpdesk_atenciones (
                        codigo_ticket, id_empresa, id_cliente, id_contacto,
                        tipo_usuario, documento_solicitante, nombre_solicitante,
                        categoria, id_subcategoria, canal_atencion, id_tecnico,
                        descripcion_requerimiento, acciones_realizadas,
                        estado_cierre, nivel_satisfaccion,
                        acciones_programadas, fecha_proxima_revision, id_tecnico_seguimiento,
                        fecha_atencion, hora_inicio, hora_fin,
                        tiempo_pausa_segundos, tiempo_efectivo_segundos
                    ) VALUES (
                        :codigo_ticket, :id_empresa, :id_cliente, :id_contacto,
                        :tipo_usuario, :documento_solicitante, :nombre_solicitante,
                        :categoria, :id_subcategoria, :canal_atencion, :id_tecnico,
                        :descripcion_requerimiento, :acciones_realizadas,
                        :estado_cierre, :nivel_satisfaccion,
                        :acciones_programadas, :fecha_proxima_revision, :id_tecnico_seguimiento,
                        :fecha_atencion, :hora_inicio, :hora_fin,
                        :tiempo_pausa_segundos, :tiempo_efectivo_segundos
                    )";

            $stmt = $this->db->prepare($sql);

            // 1. Identificadores principales (Obligatorios)
            $stmt->bindValue(':codigo_ticket', $codigoTicket, PDO::PARAM_STR);
            $stmt->bindValue(':id_empresa', (int)$datos['id_empresa'], PDO::PARAM_INT);
            $stmt->bindValue(':id_cliente', (int)$datos['id_cliente'], PDO::PARAM_INT);
            
            // 2. Contacto (Opcional - Manejo estricto de NULL)
            $idContacto = (!empty($datos['id_contacto']) && is_numeric($datos['id_contacto'])) ? (int)$datos['id_contacto'] : null;
            if ($idContacto !== null) {
                $stmt->bindValue(':id_contacto', $idContacto, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':id_contacto', null, PDO::PARAM_NULL);
            }

            // 3. Datos del Solicitante
            $stmt->bindValue(':tipo_usuario', $datos['tipo_usuario'], PDO::PARAM_STR);
            $stmt->bindValue(':documento_solicitante', $datos['documento_solicitante'], PDO::PARAM_STR);
            $stmt->bindValue(':nombre_solicitante', $datos['nombre_solicitante'], PDO::PARAM_STR);

            // 4. Clasificación (Categoría y Subcategoría) y Técnico
            $stmt->bindValue(':categoria', (int)$datos['categoria'], PDO::PARAM_INT);

            $idSubcat = (!empty($datos['id_subcategoria']) && is_numeric($datos['id_subcategoria'])) ? (int)$datos['id_subcategoria'] : null;
            if ($idSubcat !== null) {
                $stmt->bindValue(':id_subcategoria', $idSubcat, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':id_subcategoria', null, PDO::PARAM_NULL);
            }

            $stmt->bindValue(':canal_atencion', $datos['canal_atencion'], PDO::PARAM_STR);
            $stmt->bindValue(':id_tecnico', (int)$datos['id_tecnico'], PDO::PARAM_INT);

            // 5. Descripciones y Estado
            $stmt->bindValue(':descripcion_requerimiento', $datos['descripcion_requerimiento'], PDO::PARAM_STR);
            $stmt->bindValue(':acciones_realizadas', $datos['acciones_realizadas'], PDO::PARAM_STR);
            $stmt->bindValue(':estado_cierre', $datos['estado_cierre'], PDO::PARAM_STR);

            // 6. Nivel de Satisfacción (Opcional)
            $nivelSatisfaccion = (!empty($datos['nivel_satisfaccion']) && is_numeric($datos['nivel_satisfaccion'])) ? (int)$datos['nivel_satisfaccion'] : null;
            if ($nivelSatisfaccion !== null) {
                $stmt->bindValue(':nivel_satisfaccion', $nivelSatisfaccion, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':nivel_satisfaccion', null, PDO::PARAM_NULL);
            }

            // 7. Campos de Reagendamiento (Opcionales - Manejo de NULL)
            $accionesProgramadas = !empty($datos['acciones_programadas']) ? $datos['acciones_programadas'] : null;
            if ($accionesProgramadas !== null) {
                $stmt->bindValue(':acciones_programadas', $accionesProgramadas, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':acciones_programadas', null, PDO::PARAM_NULL);
            }

            $fechaProxima = !empty($datos['fecha_proxima_revision']) ? $datos['fecha_proxima_revision'] : null;
            if ($fechaProxima !== null) {
                $stmt->bindValue(':fecha_proxima_revision', $fechaProxima, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':fecha_proxima_revision', null, PDO::PARAM_NULL);
            }

            $idTecnicoSeguimiento = (!empty($datos['id_tecnico_seguimiento']) && is_numeric($datos['id_tecnico_seguimiento'])) ? (int)$datos['id_tecnico_seguimiento'] : null;
            if ($idTecnicoSeguimiento !== null) {
                $stmt->bindValue(':id_tecnico_seguimiento', $idTecnicoSeguimiento, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':id_tecnico_seguimiento', null, PDO::PARAM_NULL);
            }

            // 8. Métricas de Tiempo
            $stmt->bindValue(':fecha_atencion', $fechaAtencion, PDO::PARAM_STR);
            $stmt->bindValue(':hora_inicio', $datos['hora_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(':hora_fin', $datos['hora_fin'], PDO::PARAM_STR);
            $stmt->bindValue(':tiempo_pausa_segundos', (int)$datos['tiempo_pausa_segundos'], PDO::PARAM_INT);
            $stmt->bindValue(':tiempo_efectivo_segundos', (int)$datos['tiempo_efectivo_segundos'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'codigo_ticket' => $codigoTicket,
                    'id_atencion' => $this->db->lastInsertId()
                ];
            }

            return ['success' => false];

        } catch (PDOException $e) {
            error_log("Error PDO en guardarAtencion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ];
        }
    }
}