<?php
require_once __DIR__ . '/../config/database.php'; // Incluye el archivo de configuración para la conexión a la base de datos.

class OfertaLaboral {
    private $conn; // Variable para almacenar la conexión a la base de datos.

    public function __construct() {
        // Constructor: Establece la conexión a la base de datos utilizando la clase Database.
        $this->conn = Database::connect();
    }

    public function getAll() {
        // Recupera todos los registros de la tabla OfertaLaboral.
        $result = $this->conn->query("SELECT * FROM OfertaLaboral"); // Ejecuta la consulta SQL.
        $ofertas = []; // Array para almacenar los registros.
        while ($row = $result->fetch_assoc()) { // Itera sobre los resultados.
            $ofertas[] = $row; // Agrega cada registro al array.
        }

        // Verifica si no hay registros.
        if (empty($ofertas)) {
            return ["mensaje" => "No hay ofertas laborales disponibles"]; // Devuelve un mensaje indicando que no hay ofertas.
        }

        return $ofertas; // Devuelve el array con todos los registros.
    }

    public function create($data) {
        // Crea una nueva oferta laboral en la tabla OfertaLaboral.

        // Verificar que el reclutador_id exista y sea un Reclutador.
        $check = $this->conn->prepare("SELECT rol FROM Usuario WHERE id = ?");
        $check->bind_param("i", $data["reclutador_id"]); // Vincula el ID del reclutador.
        $check->execute(); // Ejecuta la consulta.
        $result = $check->get_result(); // Obtiene el resultado de la consulta.

        if ($result->num_rows === 0) return ["error" => "Usuario no existe"]; // Verifica si el usuario existe.
        $rol = $result->fetch_assoc()["rol"]; // Obtiene el rol del usuario.
        if ($rol !== 'Reclutador') return ["error" => "Solo un Reclutador puede crear ofertas"]; // Verifica si el rol es Reclutador.

        // Validar tipo_contrato y estado, asignar valores por defecto si no están presentes.
        $data['tipo_contrato'] = $data['tipo_contrato'] ?? 'Indefinido';
        $data['estado'] = $data['estado'] ?? 'Vigente';

        if (!in_array($data['tipo_contrato'], ['Indefinido', 'Temporal', 'Honorarios', 'Práctica'])) {
            throw new Exception("El tipo de contrato no es válido");
        }

        if (!in_array($data['estado'], ['Vigente', 'Cerrada', 'Baja'])) {
            throw new Exception("El estado no es válido");
        }

        // Prepara la consulta SQL para insertar una nueva oferta laboral.
        $stmt = $this->conn->prepare("INSERT INTO OfertaLaboral 
            (titulo, descripcion, ubicacion, salario, tipo_contrato, fecha_cierre, estado, reclutador_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsssi",
            $data["titulo"], // Título de la oferta.
            $data["descripcion"], // Descripción de la oferta.
            $data["ubicacion"], // Ubicación de la oferta.
            $data["salario"], // Salario ofrecido.
            $data["tipo_contrato"], // Tipo de contrato.
            $data["fecha_cierre"], // Fecha de cierre de la oferta.
            $data["estado"], // Estado de la oferta (por ejemplo, activa o inactiva).
            $data["reclutador_id"] // ID del reclutador que crea la oferta.
        );
        $stmt->execute(); // Ejecuta la consulta.
        return $stmt->affected_rows > 0 ? ["mensaje" => "Oferta creada"] : ["error" => "Error al crear oferta"]; // Devuelve un mensaje según el resultado.
    }

    public function update($id, $data) {
        // Actualiza un registro específico en la tabla OfertaLaboral.
        $stmt = $this->conn->prepare("UPDATE OfertaLaboral SET titulo=?, descripcion=?, ubicacion=?, salario=?, tipo_contrato=?, fecha_cierre=?, estado=? WHERE id=?");
        $stmt->bind_param("sssdsssi",
            $data["titulo"], // Título de la oferta.
            $data["descripcion"], // Descripción de la oferta.
            $data["ubicacion"], // Ubicación de la oferta.
            $data["salario"], // Salario ofrecido.
            $data["tipo_contrato"], // Tipo de contrato.
            $data["fecha_cierre"], // Fecha de cierre de la oferta.
            $data["estado"], // Estado de la oferta.
            $id // ID del registro a actualizar.
        );
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function delete($id) {
        // Elimina un registro específico de la tabla OfertaLaboral basado en su ID.
        $stmt = $this->conn->prepare("DELETE FROM OfertaLaboral WHERE id = ?");
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function desactivar($id) {
        // Cambia el estado de una oferta laboral a 'Baja'.
        $stmt = $this->conn->prepare("UPDATE OfertaLaboral SET estado = 'Baja' WHERE id = ?");
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function actualizarEstado($id, $estado) {
        // Actualiza el estado de una oferta laboral.
        $stmt = $this->conn->prepare("UPDATE OfertaLaboral SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $id); // Vincula el estado y el ID como parámetros.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function actualizarParcial($id, $data) {
        // Permite actualizar campos específicos de un registro de forma dinámica.
        // Construye dinámicamente la consulta SQL.
        $fields = [];
        $params = [];
        $types = "";

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
            $types .= is_int($value) ? "i" : "s"; // Determina el tipo de dato (i para entero, s para cadena).
        }

        $sql = "UPDATE OfertaLaboral SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i"; // El ID siempre es un entero.

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $stmt->affected_rows > 0;
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
    }

    public function obtenerPorId($id) {
        // Recupera un registro específico de la tabla OfertaLaboral basado en su ID.
        $sql = "SELECT * FROM OfertaLaboral WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.
        return $result->fetch_assoc(); // Devuelve el registro como un array asociativo.
    }

    public function getVigentes() {
        // Consulta para obtener las ofertas laborales con estado 'Vigente'.
        $sql = "SELECT * FROM OfertaLaboral WHERE estado = 'Vigente'";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.

        $ofertas = [];
        while ($row = $result->fetch_assoc()) {
            $ofertas[] = $row; // Agrega cada oferta al array.
        }

        // Verifica si no hay ofertas vigentes.
        if (empty($ofertas)) {
            return ["mensaje" => "No hay ofertas laborales vigentes disponibles"];
        }

        return $ofertas; // Devuelve el array con las ofertas vigentes.
    }
}
?>
