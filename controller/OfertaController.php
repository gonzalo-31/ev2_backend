<?php
require_once __DIR__ . '/../model/OfertaLaboral.php'; // Incluye el modelo de OfertaLaboral.

class OfertaController {
    private $oferta; // Instancia del modelo OfertaLaboral.

    public function __construct() {
        // Constructor: Inicializa el modelo OfertaLaboral.
        $this->oferta = new OfertaLaboral();
    }

    public function handleRequest($method, $input, $id = null) {
        // Maneja las solicitudes HTTP según el método.
        switch ($method) {
            case 'GET':
                if (isset($_GET['estado']) && $_GET['estado'] === 'vigentes') {
                    try {
                        $resultado = $this->oferta->getVigentes();
                        http_response_code(200); // Código 200: OK
                        echo json_encode($resultado, JSON_PRETTY_PRINT);
                    } catch (Exception $e) {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                    }
                } else {
                    // Obtiene todas las ofertas laborales.
                    http_response_code(200); // Código 200: OK
                    echo json_encode($this->oferta->getAll(), JSON_PRETTY_PRINT);
                }
                break;

            case 'POST':
                // Crea una nueva oferta laboral.
                try {
                    $resultado = $this->oferta->create($input);
                    http_response_code(201); // Código 201: Created
                    echo json_encode($resultado, JSON_PRETTY_PRINT);
                } catch (Exception $e) {
                    http_response_code(400); // Código de error 400: Solicitud incorrecta.
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;

            case 'PUT':
                // Verifica si se proporcionó un ID.
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para actualizar o desactivar"], JSON_PRETTY_PRINT);
                    return;
                }

                // Maneja la acción "desactivar".
                if (isset($input['accion']) && $input['accion'] === 'desactivar') {
                    $success = $this->oferta->desactivar($id);
                    if ($success) {
                        http_response_code(200); // Código 200: OK
                        echo json_encode(["mensaje" => "Oferta desactivada"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => "Error al desactivar la oferta"], JSON_PRETTY_PRINT);
                    }
                    return;
                }

                // Si no es "desactivar", se asume que es una actualización completa.
                try {
                    $resultado = $this->oferta->update($id, $input);
                    if ($resultado) {
                        http_response_code(200); // Código 200: OK
                        echo json_encode(["mensaje" => "Oferta laboral actualizada exitosamente"], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(500); // Código 500: Internal Server Error
                        echo json_encode(["error" => "Error al actualizar la oferta laboral"], JSON_PRETTY_PRINT);
                    }
                } catch (Exception $e) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => $e->getMessage()], JSON_PRETTY_PRINT);
                }
                break;

            case 'PATCH':
                // Actualiza parcialmente una oferta laboral.
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para actualizar"], JSON_PRETTY_PRINT);
                    return;
                }
                $this->actualizarParcialOfertaLaboral($id, $input);
                break;

            case 'DELETE':
                // Elimina una oferta laboral.
                if (!$id) {
                    http_response_code(400); // Código 400: Bad Request
                    echo json_encode(["error" => "ID requerido para eliminar"], JSON_PRETTY_PRINT);
                    return;
                }
                $success = $this->oferta->delete($id);
                if ($success) {
                    http_response_code(200); // Código 200: OK
                    echo json_encode(["mensaje" => "Oferta eliminada"], JSON_PRETTY_PRINT);
                } else {
                    http_response_code(500); // Código 500: Internal Server Error
                    echo json_encode(["error" => "Error al eliminar"], JSON_PRETTY_PRINT);
                }
                break;

            default:
                // Método no soportado.
                http_response_code(405); // Código 405: Method Not Allowed
                echo json_encode(["error" => "Método no soportado"], JSON_PRETTY_PRINT);
        }
    }

    private function actualizarParcialOfertaLaboral($id, $data) {
        // Actualiza parcialmente una oferta laboral.
        $oferta = $this->oferta->obtenerPorId($id);
        if (!$oferta) {
            http_response_code(404); // Código 404: Not Found
            echo json_encode(["error" => "La oferta laboral no existe"], JSON_PRETTY_PRINT);
            return;
        }

        if (empty($data)) {
            http_response_code(400); // Código 400: Bad Request
            echo json_encode(["error" => "No se proporcionaron datos para actualizar"], JSON_PRETTY_PRINT);
            return;
        }

        $resultado = $this->oferta->actualizarParcial($id, $data);

        if ($resultado) {
            http_response_code(200); // Código 200: OK
            echo json_encode(["mensaje" => "Oferta laboral actualizada parcialmente"], JSON_PRETTY_PRINT);
        } else {
            http_response_code(500); // Código 500: Internal Server Error
            echo json_encode(["error" => "Error al actualizar parcialmente la oferta laboral"], JSON_PRETTY_PRINT);
        }
    }
}
?>

