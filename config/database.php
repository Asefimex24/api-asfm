<?php
class Database {
    private $host = "localhost";
    private $db_name = "asfm"; // Cambia por el nombre de tu BD
    private $username = "root";        // Cambia por tu usuario
    private $password = "";     // Cambia por tu contraseña
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // En caso de error de conexión devuelve una respuesta JSON
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "mensaje" => "Error de conexión a la base de datos: " . $exception->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}
?>