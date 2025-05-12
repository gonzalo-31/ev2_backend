<?php
require_once __DIR__ . '/../model/AntecedenteAcademico.php'; // Incluye el modelo de AntecedenteAcademico.
require_once __DIR__ . '/../model/Usuario.php'; // Incluye el modelo de Usuario.

class AntecedenteAcademicoController {
    private $model; // Instancia del modelo AntecedenteAcademico.
    private $usuarioModel; // Instancia del modelo Usuario.

    public function __construct($dbConnection) {
        // Constructor: Inicializa los modelos con la conexión a la base de datos.
        $this->model = new AntecedenteAcademico($dbConnection);
        $this->usuarioModel = new Usuario($dbConnection);
    }

    public function handleRequest($method, $input, $id = null) {
        // Maneja las solicitudes HTTP según el método.
        switch ($method) {
            case 'POST':
                $this->registrarAntecedenteAcademico($input); // Crear un nuevo antecedente académico.
                break;
            case 'GET':
                $this->obtenerAntecedentesAcademicos($id); // Obtener uno o todos los antecedentes académicos.
                break;
            case 'PUT':
                $this->actualizarAntecedenteAcademico($id, $input); // Actualizar un antecedente académico.
                break;
            case 'DELETE':
                $this->eliminarAntecedenteAcademico($id); // Eliminar un antecedente académico.
                break;
            case 'PATCH':
                $this->actualizarParcialAntecedenteAcademico($id, $input); // Actualización parcial de un antecedente académico.
                break;
            default:
                http_response_code(405); // Método no permitido.
                echo json_encode(["error" => "Método no permitido"]);
                break;
        }
    }

    private function registrarAntecedenteAcademico($data) {
        // Registra un nuevo antecedente académico.
        if (!isset($data['candidato_id'], $data['institucion'], $data['titulo_obtenido'], $data['anio_ingreso'], $data['anio_egreso'])) {
            // Verifica que todos los datos requeridos estén presentes.
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        // Verifica que el usuario sea un candidato.
        $usuario = $this->usuarioModel->obtenerPorId($data['candidato_id']);
        if (!$usuario || $usuario['rol'] !== 'Candidato') {
            http_response_code(403);
            echo json_encode(["error" => "Solo los usuarios con rol de Candidato pueden registrar antecedentes académicos"]);
            return;
        }

        // Inserta el antecedente académico en la base de datos.
        $resultado = $this->model->insertar($data);

        if ($resultado) {
            http_response_code(201); // Creado exitosamente.
            echo json_encode(["mensaje" => "Antecedente académico registrado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al registrar el antecedente académico"]);
        }
    }

    private function obtenerAntecedentesAcademicos($id) {
        // Obtiene uno o todos los antecedentes académicos.
        if ($id) {
            // Si se proporciona un ID, obtiene un antecedente específico.
            $antecedente = $this->model->obtenerPorId($id);
            if ($antecedente) {
                http_response_code(200); // Éxito.
                echo json_encode($antecedente);
            } else {
                http_response_code(404); // No encontrado.
                echo json_encode(["error" => "Antecedente académico no encontrado"]);
            }
        } else {
            // Si no se proporciona un ID, obtiene todos los antecedentes.
            $antecedentes = $this->model->obtenerTodos();
            http_response_code(200); // Éxito.
            echo json_encode($antecedentes);
        }
    }

    private function actualizarAntecedenteAcademico($id, $data) {
        // Actualiza un antecedente académico completo.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente académico es requerido"]);
            return;
        }

        $resultado = $this->model->actualizar($id, $data);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Antecedente académico actualizado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar el antecedente académico"]);
        }
    }

    private function eliminarAntecedenteAcademico($id) {
        // Elimina un antecedente académico.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente académico es requerido"]);
            return;
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            http_response_code(200); // Éxito.
            echo json_encode(["mensaje" => "Antecedente académico eliminado exitosamente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al eliminar el antecedente académico"]);
        }
    }

    private function actualizarParcialAntecedenteAcademico($id, $data) {
        // Actualiza parcialmente un antecedente académico.
        if (!$id) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "ID del antecedente académico es requerido"]);
            return;
        }

        // Verifica si el antecedente académico existe.
        $antecedente = $this->model->obtenerPorId($id);
        if (!$antecedente) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "El ID no existe"]);
            return;
        }

        // Verifica si el usuario asociado al antecedente existe.
        $usuario = $this->usuarioModel->obtenerPorId($antecedente['candidato_id']);
        if (!$usuario) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "El usuario asociado al antecedente académico no existe"]);
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
            echo json_encode(["mensaje" => "Antecedente académico actualizado parcialmente"]);
        } else {
            http_response_code(500); // Error interno del servidor.
            echo json_encode(["error" => "Error al actualizar parcialmente el antecedente académico"]);
        }
    }
}
?>
