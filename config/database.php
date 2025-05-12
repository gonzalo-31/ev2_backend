<?php
class Database {
    public static function connect() {
        $conn = new mysqli("localhost", "root", "", "cliente_feliz");
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }
        echo " ";
        return $conn;
    }
}
?>
