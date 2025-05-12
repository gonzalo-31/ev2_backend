<?php
// Habilitar CORS (Cross-Origin Resource Sharing) para permitir solicitudes desde cualquier origen.
header("Access-Control-Allow-Origin: *"); // Permitir acceso desde cualquier origen.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Métodos HTTP permitidos.
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Headers permitidos.
header("Content-Type: application/json"); // Tipo de contenido por defecto para las respuestas.

// Manejar solicitudes OPTIONS (preflight) para CORS.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Responder con éxito para solicitudes preflight.
    exit();
}

// Incluir los archivos necesarios para la configuración y los controladores.
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/PostulacionController.php';
require_once __DIR__ . '/../controller/UsuarioController.php';
require_once __DIR__ . '/../controller/OfertaController.php';
require_once __DIR__ . '/../controller/AntecedenteLaboralController.php';
require_once __DIR__ . '/../controller/AntecedenteAcademicoController.php';

// Crear conexión a la base de datos utilizando la clase Database.
$dbConnection = Database::connect();

// Obtener el método HTTP de la solicitud (GET, POST, PUT, DELETE, etc.).
$method = $_SERVER['REQUEST_METHOD'];

// Obtener el parámetro 'path' de la URL para determinar la ruta solicitada.
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Leer el cuerpo de la solicitud (para métodos como POST, PUT y PATCH).
$input = json_decode(file_get_contents('php://input'), true);

// Obtener el parámetro 'id' de la URL (si está presente).
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Manejar las rutas según el valor de 'path'.
switch ($path) {
    case 'postulacion':
        // Ruta para manejar solicitudes relacionadas con postulaciones.
        $postulacionController = new PostulacionController($dbConnection);
        $postulacionController->handleRequest($method, $input, $id);
        break;

    case 'usuario':
        // Ruta para manejar solicitudes relacionadas con usuarios.
        $usuarioController = new UsuarioController($dbConnection);
        $usuarioController->handleRequest($method, $input, $id);
        break;

    case 'oferta_laboral':
        // Ruta para manejar solicitudes relacionadas con ofertas laborales.
        $ofertaController = new OfertaController($dbConnection);
        $ofertaController->handleRequest($method, $input, $id);
        break;

    case 'antecedente_laboral':
        // Ruta para manejar solicitudes relacionadas con antecedentes laborales.
        $antecedenteLaboralController = new AntecedenteLaboralController($dbConnection);
        $antecedenteLaboralController->handleRequest($method, $input, $id);
        break;

    case 'antecedente_academico':
        // Ruta para manejar solicitudes relacionadas con antecedentes académicos.
        $antecedenteAcademicoController = new AntecedenteAcademicoController($dbConnection);
        $antecedenteAcademicoController->handleRequest($method, $input, $id);
        break;

    default:
        // Responder con un error 404 si la ruta no coincide con ninguna de las anteriores.
        http_response_code(404);
        echo json_encode(["error" => "Ruta no encontrada"]);
        break;
}
?>