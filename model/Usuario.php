<?php
require_once __DIR__ . '/../config/database.php'; // Incluye el archivo de configuración para la conexión a la base de datos.

class Usuario {
    private $conn; // Variable para almacenar la conexión a la base de datos.

    public function __construct() {
        // Constructor: Establece la conexión a la base de datos utilizando la clase Database.
        $this->conn = Database::connect();
    }
    public function getAll() {
        // Recupera todos los registros de la tabla Usuario.
        $result = $this->conn->query("SELECT * FROM Usuario"); // Ejecuta la consulta SQL.
        $usuarios = []; // Array para almacenar los registros.
        while ($row = $result->fetch_assoc()) { // Itera sobre los resultados.
            $usuarios[] = $row; // Agrega cada registro al array.
        }
        if (empty($usuarios)) { // Verifica si no hay registros.
            return "No hay registros"; // Devuelve un mensaje indicando que no hay registros.
        }
        return $usuarios; // Devuelve el array con todos los registros.
    }

    public function create($data) {
        // Inserta un nuevo registro en la tabla Usuario.
        $requiredFields = ['nombre', 'apellido', 'email', 'contraseña', 'fecha_nacimiento', 'telefono', 'direccion', 'rol'];
        foreach ($requiredFields as $field) {
            // Verifica que todos los campos obligatorios estén presentes y no estén vacíos.
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("El campo '$field' es obligatorio"); // Lanza una excepción si falta algún campo.
            }
        }

        // Validar que el rol sea "Candidato" o "Reclutador".
        if (!in_array($data['rol'], ['Candidato', 'Reclutador'])) {
            throw new Exception("El rol debe ser 'Candidato' o 'Reclutador'"); // Lanza una excepción si el rol no es válido.
        }

        // Hashea la contraseña para almacenarla de forma segura.
        $hashedPassword = password_hash($data['contraseña'], PASSWORD_DEFAULT);

        // Prepara la consulta SQL para insertar un nuevo registro.
        $stmt = $this->conn->prepare("INSERT INTO Usuario (nombre, apellido, email, contraseña, fecha_nacimiento, telefono, direccion, rol)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", 
            $data['nombre'], // Nombre del usuario.
            $data['apellido'], // Apellido del usuario.
            $data['email'], // Email del usuario.
            $hashedPassword, // Contraseña hasheada.
            $data['fecha_nacimiento'], // Fecha de nacimiento.
            $data['telefono'], // Teléfono.
            $data['direccion'], // Dirección.
            $data['rol'] // Rol del usuario.
        );
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }

    public function update($id, $data) {
        // Verifica si el usuario existe.
        $usuario = $this->obtenerPorId($id);
        if (!$usuario) {
            throw new Exception("El usuario con ID $id no existe");
        }

        // Recupera los datos actuales del usuario.
        $usuarioActual = $this->obtenerPorId($id);

        // Compara los datos enviados con los actuales.
        foreach ($data as $key => $value) {
            if (isset($usuarioActual[$key]) && $usuarioActual[$key] === $value) {
                unset($data[$key]); // Elimina los campos que no han cambiado.
            }
        }

        // Si no hay cambios, lanza una excepción.
        if (empty($data)) {
            throw new Exception("No se realizaron cambios en el usuario");
        }

        // Validar que el rol sea "Candidato" o "Reclutador" si está presente en los datos.
        if (isset($data['rol']) && !in_array($data['rol'], ['Candidato', 'Reclutador'])) {
            throw new Exception("El rol debe ser 'Candidato' o 'Reclutador'");
        }

        // Construye dinámicamente la consulta SQL.
        $fields = [];
        $params = [];
        $types = "";

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
            $types .= is_int($value) ? "i" : "s";
        }

        $sql = "UPDATE Usuario SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("No se realizaron cambios en el usuario");
        }

        return true;
    }

    public function delete($id) {
        // Elimina un registro específico de la tabla Usuario basado en su ID.
        $stmt = $this->conn->prepare("DELETE FROM Usuario WHERE id = ?");
        $stmt->bind_param("i", $id); // Asigna el ID como parámetro.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }
    
    public function obtenerPorId($id) {
        // Recupera un registro específico de la tabla Usuario basado en su ID.
        $sql = "SELECT id, rol FROM Usuario WHERE id = ?";
        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param("i", $id); // Asigna el ID como parámetro.
        $stmt->execute(); // Ejecuta la consulta.
        $result = $stmt->get_result(); // Obtiene el resultado de la consulta.
        return $result->fetch_assoc(); // Devuelve el registro como un array asociativo.
    }

    public function actualizarParcial($id, $data) {
        // Validar que el rol sea "Candidato" o "Reclutador" si está presente en los datos.
        if (isset($data['rol']) && !in_array($data['rol'], ['Candidato', 'Reclutador'])) {
            throw new Exception("El rol debe ser 'Candidato' o 'Reclutador'"); // Lanza una excepción si el rol no es válido.
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

        $sql = "UPDATE Usuario SET " . implode(", ", $fields) . " WHERE id = ?"; // Construye la consulta SQL.
        $params[] = $id; // Agrega el ID al final de los parámetros.
        $types .= "i"; // Agrega el tipo de dato para el ID.

        $stmt = $this->conn->prepare($sql); // Prepara la consulta SQL.
        $stmt->bind_param($types, ...$params); // Asigna los parámetros dinámicamente.
        return $stmt->execute(); // Ejecuta la consulta y devuelve true si tiene éxito.
    }
}
?>
