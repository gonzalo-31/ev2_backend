<?php
require_once __DIR__ . '/../config/database.php'; // Incluye el archivo de configuración para la conexión a la base de datos.

class Postulacion {
    private $conn; // Variable para almacenar la conexión a la base de datos.

    public function __construct() {
        // Constructor: Establece la conexión a la base de datos utilizando la clase Database.
        $this->conn = Database::connect();
    }

    public function insertar($data) {
        // Inserta un nuevo registro en la tabla Postulacion.
        $sql = "INSERT INTO Postulacion (candidato_id, oferta_laboral_id, comentario) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        return $stmt->execute([
            $data['candidato_id'], // ID del candidato.
            $data['oferta_laboral_id'], // ID de la oferta laboral.
            $data['comentario'] ?? null // Comentario opcional.
        ]);
    }

    public function obtener($filtros = []) {
        // Recupera registros de la tabla Postulacion con filtros opcionales.
        $sql = "SELECT * FROM Postulacion"; // Consulta base.
        $params = []; // Almacena los valores de los filtros.
        $conditions = []; // Almacena las condiciones de los filtros.

        // Filtrar por ID de la postulación.
        if (isset($filtros['id'])) {
            $conditions[] = "id = ?";
            $params[] = $filtros['id'];
        }

        // Filtrar por candidato_id.
        if (isset($filtros['candidato_id'])) {
            $conditions[] = "candidato_id = ?";
            $params[] = $filtros['candidato_id'];
        }

        // Filtrar por oferta_laboral_id.
        if (isset($filtros['oferta_laboral_id'])) {
            $conditions[] = "oferta_laboral_id = ?";
            $params[] = $filtros['oferta_laboral_id'];
        }

        // Agregar condiciones a la consulta si existen.
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.

        // Vincular parámetros dinámicamente si existen.
        if (!empty($params)) {
            $stmt->bind_param(str_repeat("i", count($params)), ...$params);
        }

        $stmt->execute(); // Ejecuta la consulta.
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Devuelve un array asociativo con los resultados.
    }

    public function actualizar($id, $data) {
        // Validar que los datos requeridos estén presentes.
        if (empty($data['estado_postulacion'])) {
            throw new Exception("El campo 'estado_postulacion' es obligatorio");
        }

        if (empty($data['comentario'])) {
            throw new Exception("El campo 'comentario' es obligatorio");
        }

        // Construir la consulta SQL.
        $sql = "UPDATE Postulacion SET estado_postulacion = ?, comentario = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        // Asignar los valores a variables.
        $estadoPostulacion = $data['estado_postulacion'];
        $comentario = $data['comentario'];

        // Vincular las variables a la consulta.
        $stmt->bind_param(
            "ssi", // Tipos de datos: s (cadena), s (cadena), i (entero).
            $estadoPostulacion,
            $comentario,
            $id
        );

        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function actualizarParcial($id, $data) {
        // Validar que el comentario esté presente.
        if (isset($data['estado_postulacion']) && empty($data['comentario'])) {
            throw new Exception("El campo 'comentario' es obligatorio al actualizar el estado");
        }

        // Permite actualizar campos específicos de un registro de forma dinámica.
        $fields = []; // Almacena los campos a actualizar.
        $params = []; // Almacena los valores de los campos.
        $types = ""; // Almacena los tipos de datos para bind_param.

        // Construir dinámicamente los campos a actualizar.
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?"; // Agrega el campo con un marcador de posición.
            $params[] = $value; // Agrega el valor correspondiente.
            $types .= is_int($value) ? "i" : "s"; // Determina el tipo de dato (i para entero, s para cadena).
        }

        $sql = "UPDATE Postulacion SET " . implode(", ", $fields) . " WHERE id = ?"; // Construye la consulta SQL.
        $params[] = $id; // Agrega el ID al final de los parámetros.
        $types .= "i"; // Agrega el tipo de dato para el ID.

        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param($types, ...$params); // Asigna los parámetros dinámicamente.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function obtenerPostulantesPorOferta($ofertaId) {
        // Recupera los postulantes asociados a una oferta laboral específica.
        $sql = "SELECT 
                    p.id AS postulacion_id, 
                    u.id AS candidato_id, 
                    u.nombre, 
                    u.apellido, 
                    u.email, 
                    p.estado_postulacion, 
                    p.comentario, 
                    p.fecha_postulacion
                FROM Postulacion p
                INNER JOIN Usuario u ON p.candidato_id = u.id
                WHERE p.oferta_laboral_id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $ofertaId); // Vincula el ID de la oferta laboral.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.
        return $result->fetch_all(MYSQLI_ASSOC); // Devuelve un array asociativo con los resultados.
    }

    public function obtenerPostulantesPorTodasLasOfertas() {
        // Consulta para obtener todas las ofertas laborales.
        $sqlOfertas = "SELECT id, titulo FROM OfertaLaboral";
        $stmtOfertas = $this->conn->prepare($sqlOfertas);
        $stmtOfertas->execute();
        $resultOfertas = $stmtOfertas->get_result();
        $ofertas = $resultOfertas->fetch_all(MYSQLI_ASSOC);

        $resultado = [];

        // Para cada oferta laboral, obtener los postulantes asociados.
        foreach ($ofertas as $oferta) {
            $ofertaId = $oferta['id'];
            $postulantes = $this->obtenerPostulantesPorOferta($ofertaId);

            $resultado[] = [
                "oferta_id" => $ofertaId,
                "titulo" => $oferta['titulo'],
                "postulantes" => $postulantes
            ];
        }

        return $resultado;
    }

    public function obtenerPorId($id) {
        // Recupera un registro específico de la tabla Postulacion basado en su ID.
        $sql = "SELECT * FROM Postulacion WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.
        return $result->fetch_assoc(); // Devuelve el registro como un array asociativo.
    }

    public function eliminar($id) {
        $sql = "DELETE FROM Postulacion WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        $stmt->execute();

        // Devuelve true si se eliminó al menos una fila, false si no.
        return $stmt->affected_rows > 0;
    }

    public function obtenerEstadoYComentarios($candidatoId) {
        // Consulta para obtener el estado y los comentarios de las postulaciones de un candidato.
        $sql = "SELECT 
                    p.id AS postulacion_id,
                    p.estado_postulacion,
                    p.comentario,
                    o.titulo AS oferta_titulo,
                    o.descripcion AS oferta_descripcion
                FROM Postulacion p
                INNER JOIN OfertaLaboral o ON p.oferta_laboral_id = o.id
                WHERE p.candidato_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $candidatoId); // Vincula el ID del candidato.
        $stmt->execute();
        $result = $stmt->get_result();

        $postulaciones = [];
        while ($row = $result->fetch_assoc()) {
            $postulaciones[] = $row; // Agrega cada postulación al array.
        }

        // Verifica si no hay postulaciones para el candidato.
        if (empty($postulaciones)) {
            return ["mensaje" => "No tienes postulaciones registradas"];
        }

        return $postulaciones; // Devuelve el array con las postulaciones.
    }
}
?>
