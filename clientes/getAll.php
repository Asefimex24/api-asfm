<?php
// Encabezados HTTP
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo del preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
    exit();
}

// Incluir configuración de base de datos desde la carpeta raíz
require_once __DIR__ . '/../config/database.php';

// Capturar el JSON del cuerpo de la petición
$inputData = json_decode(file_get_contents("php://input"), true);

// Validar parámetro ClienteID
if (!$inputData || !isset($inputData['ClienteID']) || empty(trim($inputData['ClienteID']))) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "mensaje" => "Se requiere el parámetro 'ClienteID' dentro del JSON body."
    ]);
    exit();
}

$clienteId = trim($inputData['ClienteID']);

// Instanciar conexión y ejecutar consulta
$database = new Database();
$db = $database->getConnection();

$sql = "SELECT 
            *
        FROM CLIENTES";

try {
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':clienteId', $clienteId, PDO::PARAM_STR);
    $stmt->execute();

    // Como buscamos un cliente específico por ID, usamos fetch() en lugar de fetchAll()
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data"   => $clientes
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode([
            "status"  => "error",
            "mensaje" => "No se encontraron clientes",
            "data"    => null
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error en la consulta: " . $e->getMessage()
    ]);
}
?>