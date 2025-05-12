<?php
require_once __DIR__ . '/../model/Usuario.php'; // Incluye el modelo de Usuario.

class UsuarioController {
    private $usuario; // Instancia del modelo Usuario.

    public function __construct() {
        // Constructor: Inicializa el modelo Usuario.
        $this->usuario = new Usuario();
    }

    public function handleRequest($method, $input, $id = null) {
        // Maneja las solicitudes HTTP según el método.
        try {
            switch ($method) {
                case 'GET':
                    // Obtiene todos los usuarios.
                    $data = $this->usuario->getAll();
                    echo json_encode($data, JSON_PRETTY_PRINT);
                    break;

                case 'POST':
                    // Crea un nuevo usuario.
                    if (!$input) {
                        throw new Exception("No se recibieron datos en la solicitud");
                    }
                    $success = $this->usuario->create($input);
                    echo json_encode($success ? ["mensaje" => "Usuario creado"] : ["error" => "Error al crear"], JSON_PRETTY_PRINT);
                    break;

                case 'PUT':
                    // Actualiza un usuario completo.
                    if (!$id) {
                        echo json_encode(["error" => "ID requerido"], JSON_PRETTY_PRINT);
                        return;
                    }
                    $success = $this->usuario->update($id, $input);
                    echo json_encode($success ? ["mensaje" => "Usuario actualizado"] : ["error" => "Error al actualizar"], JSON_PRETTY_PRINT);
                    break;

                case 'PATCH':
                    // Actualiza parcialmente un usuario.
                    if (!$id) {
                        http_response_code(400); // Solicitud incorrecta.
                        echo json_encode(["error" => "ID del usuario es requerido"], JSON_PRETTY_PRINT);
                        return;
                    }
                    $this->actualizarParcialUsuario($id, $input);
                    break;

                case 'DELETE':
                    // Elimina un usuario.
                    if (!$id) {
                        http_response_code(400); // Solicitud incorrecta.
                        echo json_encode(["error" => "ID requerido para eliminar"], JSON_PRETTY_PRINT);
                        return;
                    }
                    
                    // Verifica si el usuario existe antes de eliminar.
                    $usuario = $this->usuario->obtenerPorId($id);
                    if (!$usuario) {
                        http_response_code(404); // No encontrado.
                        echo json_encode(["error" => "El usuario no existe"], JSON_PRETTY_PRINT);
                        return;
                    }

                    $success = $this->usuario->delete($id);
                    if ($success) {
                        http_response_code(200); // Éxito.
                        echo json_encode(["mensaje" => "Usuario eliminado"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(500); // Error interno del servidor.
                        echo json_encode(["error" => "Error al eliminar"], JSON_PRETTY_PRINT);
                    }
                    break;

                default:
                    // Método no soportado.
                    http_response_code(405); // Método no permitido.
                    echo json_encode(["error" => "Método no soportado"], JSON_PRETTY_PRINT);
            }
        } catch (Exception $e) {
            // Manejo de excepciones.
            echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
        }
    }

    private function actualizarParcialUsuario($id, $data) {
        // Verifica si el usuario existe.
        $usuario = $this->usuario->obtenerPorId($id);
        if (!$usuario) {
            http_response_code(404); // No encontrado.
            echo json_encode(["error" => "El usuario no existe"], JSON_PRETTY_PRINT);
            return;
        }

        // Verifica si hay datos para actualizar.
        if (empty($data)) {
            http_response_code(400); // Solicitud incorrecta.
            echo json_encode(["error" => "No se proporcionaron datos para actualizar"], JSON_PRETTY_PRINT);
            return;
        }

        try {
            // Realiza la actualización parcial.
            $resultado = $this->usuario->update($id, $data);

            if ($resultado) {
                // Actualización exitosa.
                http_response_code(200);
                echo json_encode(["mensaje" => "Usuario actualizado parcialmente"], JSON_PRETTY_PRINT);
            }
        } catch (Exception $e) {
            // Error en la actualización.
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
        }
    }
}
?>