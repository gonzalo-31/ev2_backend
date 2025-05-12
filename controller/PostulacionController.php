<?php
require_once __DIR__ . '/../model/Postulacion.php'; // Incluye el modelo de Postulacion.
require_once __DIR__ . '/../model/Usuario.php'; // Incluye el modelo de Usuario.

class PostulacionController {
    private $postulacionModel; // Instancia del modelo Postulacion.
    private $usuarioModel; // Instancia del modelo Usuario.

    public function __construct($dbConnection) {
        // Constructor: Inicializa los modelos con la conexión a la base de datos.
        $this->postulacionModel = new Postulacion($dbConnection);
        $this->usuarioModel = new Usuario($dbConnection);
    }

    public function handleRequest($method, $input, $id) {
        // Maneja las solicitudes HTTP según el método.
        switch ($method) {
            case 'GET':
                // Si se pasa un parámetro 'oferta_id', obtiene los postulantes de una oferta.
                if (isset($_GET['oferta_id'])) {
                    $this->obtenerPostulantesPorOferta($_GET['oferta_id']);
                } elseif (isset($input['todas_las_ofertas'])) {
                    // Si se solicita obtener postulantes de todas las ofertas
                    try {
                        $resultado = $this->postulacionModel->obtenerPostulantesPorTodasLasOfertas();
                        http_response_code(200); // Código 200: OK
                        echo json_encode($resultado, JSON_PRETTY_PRINT);
                    } catch (Exception $e) {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                    }
                } else {
                    // Si no, obtiene una o todas las postulaciones.
                    $this->obtenerPostulaciones($id);
                }
                break;
            case 'POST':
                // Crea una nueva postulación.
                $this->crearPostulacion($input);
                break;
            case 'PUT':
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para actualizar"], JSON_PRETTY_PRINT);
                    return;
                }

                try {
                    $resultado = $this->postulacionModel->actualizar($id, $input);
                    if ($resultado) {
                        http_response_code(200); // Código 200: OK
                        echo json_encode(["mensaje" => "Postulación actualizada exitosamente"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => "Error al actualizar la postulación"], JSON_PRETTY_PRINT);
                    }
                } catch (Exception $e) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                }
                break;
            case 'PATCH':
                // Actualiza parcialmente una postulación.
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para actualizar"], JSON_PRETTY_PRINT);
                    return;
                }

                try {
                    $resultado = $this->postulacionModel->actualizarParcial($id, $input);
                    if ($resultado) {
                        http_response_code(200); // Código 200: OK
                        echo json_encode(["mensaje" => "Postulación actualizada parcialmente"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => "Error al actualizar parcialmente la postulación"], JSON_PRETTY_PRINT);
                    }
                } catch (Exception $e) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                }
                break;
            case 'DELETE':
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para eliminar"], JSON_PRETTY_PRINT);
                    return;
                }

                try {
                    $resultado = $this->postulacionModel->eliminar($id);
                    if ($resultado) {
                        http_response_code(200); // Código 200: OK
                        echo json_encode(["mensaje" => "Postulación eliminada exitosamente"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(404); // Código 404: Not Found
                        echo json_encode(["error" => "La postulación no existe"], JSON_PRETTY_PRINT);
                    }
                } catch (Exception $e) {
                    http_response_code(500); // Código 500: Internal Server Error
                    echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                }
                break;
            default:
                // Método no permitido.
                http_response_code(405);
                echo json_encode(["error" => "Método no permitido"]);
                break;
        }
    }

    private function crearPostulacion($data) {
        // Crea una nueva postulación.
        if (!isset($data['candidato_id'], $data['oferta_laboral_id'])) {
            // Verifica que los datos requeridos estén presentes.
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        // Verifica que el usuario tenga el rol de "Candidato".
        $usuario = $this->usuarioModel->obtenerPorId($data['candidato_id']);
        if (!$usuario || $usuario['rol'] !== 'Candidato') {
            http_response_code(403);
            echo json_encode(["error" => "Solo los usuarios con rol de Candidato pueden postular"]);
            return;
        }

        // Inserta la postulación en la base de datos.
        $resultado = $this->postulacionModel->insertar($data);

        if ($resultado) {
            http_response_code(201); // Creado exitosamente.
            echo json_encode(["mensaje" => "Postulación creada exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al crear la postulación"]);
        }
    }

    private function obtenerPostulaciones($id) {
        // Obtiene una o todas las postulaciones.
        $filtros = [];

        if ($id) {
            // Si se pasa un ID, busca por ID de la postulación.
            $filtros['id'] = $id;
        }

        $postulaciones = $this->postulacionModel->obtener($filtros);

        if ($postulaciones) {
            http_response_code(200); // Éxito.
            echo json_encode($postulaciones);
        } else {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "No se encontraron postulaciones"]);
        }
    }

    private function actualizarPostulacion($id, $data) {
        // Actualiza una postulación completa.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID de la postulación es requerido"]);
            return;
        }

        if (!isset($data['estado_postulacion'], $data['comentario'], $data['reclutador_id'])) {
            // Verifica que los datos requeridos estén presentes.
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        // Verifica si el estado de la postulación es válido.
        $estadosValidos = [
            'Postulando',
            'Revisando',
            'Entrevista Psicológica',
            'Entrevista Personal',
            'Seleccionado',
            'Descartado'
        ];

        if (!in_array($data['estado_postulacion'], $estadosValidos)) {
            http_response_code(400);
            echo json_encode(["error" => "Estado de postulación no válido"]);
            return;
        }

        // Verifica que el usuario tenga el rol de "Reclutador".
        $usuario = $this->usuarioModel->obtenerPorId($data['reclutador_id']);
        if (!$usuario || $usuario['rol'] !== 'Reclutador') {
            http_response_code(403);
            echo json_encode(["error" => "Solo los usuarios con rol de Reclutador pueden actualizar el estado de la postulación"]);
            return;
        }

        $resultado = $this->postulacionModel->actualizar($id, $data);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Estado de la postulación actualizado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar la postulación"]);
        }
    }

    private function obtenerPostulantesPorOferta($id) {
        // Obtiene los postulantes asociados a una oferta laboral específica.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID de la oferta laboral es requerido"]);
            return;
        }

        $postulantes = $this->postulacionModel->obtenerPostulantesPorOferta($id);

        if ($postulantes) {
            http_response_code(200); // Éxito.
            echo json_encode($postulantes);
        } else {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "No se encontraron postulantes para esta oferta laboral"]);
        }
    }

    private function actualizarParcialPostulacion($id, $data) {
        // Actualiza parcialmente una postulación.
        $postulacion = $this->postulacionModel->obtenerPorId($id);
        if (!$postulacion) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "La postulación no existe"]);
            return;
        }

        if (empty($data)) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "No se proporcionaron datos para actualizar"]);
            return;
        }

        if (isset($data['estado_postulacion'])) {
            // Valida el estado de la postulación si se incluye.
            $estadosValidos = [
                'Postulando',
                'Revisando',
                'Entrevista Psicológica',
                'Entrevista Personal',
                'Seleccionado',
                'Descartado'
            ];

            if (!in_array($data['estado_postulacion'], $estadosValidos)) {
                http_response_code(400);
                echo json_encode(["error" => "Estado de postulación no válido"]);
                return;
            }
        }

        $resultado = $this->postulacionModel->actualizarParcial($id, $data);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Postulación actualizada parcialmente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar parcialmente la postulación"]);
        }
    }
}
?>