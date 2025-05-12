<?php
require_once __DIR__ . '/../config/database.php'; // Incluye el archivo de configuración para la conexión a la base de datos.

class AntecedenteLaboral {
    private $conn; // Variable para almacenar la conexión a la base de datos.

    public function __construct() {
        // Constructor: Establece la conexión a la base de datos utilizando la clase Database.
        $this->conn = Database::connect();
    }

    public function insertar($data) {
        // Inserta un nuevo registro en la tabla AntecedenteLaboral.
        $sql = "INSERT INTO AntecedenteLaboral (candidato_id, empresa, cargo, funciones, fecha_inicio, fecha_termino) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param(
            "isssss", // Define los tipos de datos: i (entero), s (cadena).
            $data['candidato_id'], // ID del candidato.
            $data['empresa'], // Nombre de la empresa.
            $data['cargo'], // Cargo desempeñado.
            $data['funciones'], // Funciones realizadas.
            $data['fecha_inicio'], // Fecha de inicio del trabajo.
            $data['fecha_termino'] // Fecha de término del trabajo.
        );
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function obtenerPorId($id) {
        // Recupera un registro específico de la tabla basado en su ID.
        $sql = "SELECT * FROM AntecedenteLaboral WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.
        return $result->fetch_assoc(); // Devuelve el registro como un array asociativo.
    }

    public function obtenerTodos() {
        // Recupera todos los registros de la tabla AntecedenteLaboral.
        $sql = "SELECT * FROM AntecedenteLaboral";
        $result = $this->conn->query($sql); // Ejecuta la consulta directamente.
        return $result->fetch_all(MYSQLI_ASSOC); // Devuelve todos los registros como un array asociativo.
    }

    public function actualizar($id, $data) {
        // Actualiza un registro específico en la tabla con los datos proporcionados.
        $sql = "UPDATE AntecedenteLaboral SET empresa = ?, cargo = ?, funciones = ?, fecha_inicio = ?, fecha_termino = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param(
            "sssssi", // Define los tipos de datos: s (cadena), i (entero).
            $data['empresa'], // Nombre de la empresa.
            $data['cargo'], // Cargo desempeñado.
            $data['funciones'], // Funciones realizadas.
            $data['fecha_inicio'], // Fecha de inicio del trabajo.
            $data['fecha_termino'], // Fecha de término del trabajo.
            $id // ID del registro a actualizar.
        );
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function actualizarParcial($id, $data) {
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

        $sql = "UPDATE AntecedenteLaboral SET " . implode(", ", $fields) . " WHERE id = ?"; // Construye la consulta SQL.
        $params[] = $id; // Agrega el ID al final de los parámetros.
        $types .= "i"; // Agrega el tipo de dato para el ID.

        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param($types, ...$params); // Asigna los parámetros dinámicamente.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function eliminar($id) {
        // Elimina un registro específico de la tabla AntecedenteLaboral basado en su ID.
        $sql = "DELETE FROM AntecedenteLaboral WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $id); // Vincula el ID como parámetro.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }
}
?>