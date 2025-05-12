<?php
require_once __DIR__ . '/../model/AntecedenteLaboral.php'; // Incluye el modelo de AntecedenteLaboral.
require_once __DIR__ . '/../model/Usuario.php'; // Incluye el modelo de Usuario.

class AntecedenteLaboralController {
    private $model; // Instancia del modelo AntecedenteLaboral.
    private $usuarioModel; // Instancia del modelo Usuario.

    public function __construct($dbConnection) {
        // Constructor: Inicializa los modelos con la conexión a la base de datos.
        $this->model = new AntecedenteLaboral($dbConnection);
        $this->usuarioModel = new Usuario($dbConnection);
    }

    public function handleRequest($method, $input, $id = null) {
        // Maneja las solicitudes HTTP según el método.
        switch ($method) {
            case 'POST':
                $this->crearAntecedenteLaboral($input); // Crear un nuevo antecedente laboral.
                break;
            case 'GET':
                $this->obtenerAntecedentesLaborales($id); // Obtener uno o todos los antecedentes laborales.
                break;
            case 'PUT':
                $this->actualizarAntecedenteLaboral($id, $input); // Actualizar un antecedente laboral completo.
                break;
            case 'PATCH':
                $this->actualizarParcialAntecedenteLaboral($id, $input); // Actualización parcial de un antecedente laboral.
                break;
            case 'DELETE':
                $this->eliminarAntecedenteLaboral($id); // Eliminar un antecedente laboral.
                break;
            default:
                http_response_code(405); // Método no permitido.
                echo json_encode(["error" => "Método no permitido"]);
                break;
        }
    }

    private function crearAntecedenteLaboral($data) {
        // Registra un nuevo antecedente laboral.
        if (!isset($data['candidato_id'], $data['empresa'], $data['cargo'], $data['funciones'], $data['fecha_inicio'], $data['fecha_termino'])) {
            // Verifica que todos los datos requeridos estén presentes.
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        // Verifica que el usuario sea un candidato.
        $usuario = $this->usuarioModel->obtenerPorId($data['candidato_id']);
        if (!$usuario || $usuario['rol'] !== 'Candidato') {
            http_response_code(403);
            echo json_encode(["error" => "Solo los usuarios con rol de Candidato pueden registrar antecedentes laborales"]);
            return;
        }

        // Inserta el antecedente laboral en la base de datos.
        $resultado = $this->model->insertar($data);

        if ($resultado) {
            http_response_code(201); // Creado exitosamente.
            echo json_encode(["mensaje" => "Antecedente laboral registrado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al registrar el antecedente laboral"]);
        }
    }

    private function obtenerAntecedentesLaborales($id) {
        // Obtiene uno o todos los antecedentes laborales.
        if ($id) {
            // Si se proporciona un ID, obtiene un antecedente específico.
            $antecedente = $this->model->obtenerPorId($id);
            if ($antecedente) {
                http_response_code(200); // Éxito.
                echo json_encode($antecedente);
            } else {
                http_response_code(404); // No encontrado.
                echo json_encode(["error" => "Antecedente laboral no encontrado"]);
            }
        } else {
            // Si no se proporciona un ID, obtiene todos los antecedentes.
            $antecedentes = $this->model->obtenerTodos();
            http_response_code(200); // Éxito.
            echo json_encode($antecedentes);
        }
    }

    private function actualizarAntecedenteLaboral($id, $data) {
        // Actualiza un antecedente laboral completo.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente laboral es requerido"]);
            return;
        }

        $resultado = $this->model->actualizar($id, $data);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Antecedente laboral actualizado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar el antecedente laboral"]);
        }
    }

    private function actualizarParcialAntecedenteLaboral($id, $data) {
        // Actualiza parcialmente un antecedente laboral.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente laboral es requerido"]);
            return;
        }

        // Verifica si el antecedente laboral existe.
        $antecedente = $this->model->obtenerPorId($id);
        if (!$antecedente) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "el ID existe"]);
            return;
        }

        // Verifica si el usuario asociado al antecedente existe.
        $usuario = $this->usuarioModel->obtenerPorId($antecedente['candidato_id']);
        if (!$usuario) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "El usuario asociado al antecedente laboral no existe"]);
            return;
        }

        // Verifica si hay datos para actualizar.
        if (empty($data)) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "No se proporcionaron datos para actualizar"]);
            return;
        }

        $resultado = $this->model->actualizarParcial($id, $data);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Antecedente laboral actualizado parcialmente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar parcialmente el antecedente laboral"]);
        }
    }

    private function eliminarAntecedenteLaboral($id) {
        // Elimina un antecedente laboral.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente laboral es requerido"]);
            return;
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Antecedente laboral eliminado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al eliminar el antecedente laboral"]);
        }
    }
}
?>