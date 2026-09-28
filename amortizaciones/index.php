<?php
// Encabezados HTTP
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo del preflight CORS (opcional pero recomendado para peticiones desde frontend)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar que el método sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "status" => "error",
        "mensaje" => "Método no permitido. Utiliza POST."
    ]);
    exit();
}

// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

// Capturar y decodificar el cuerpo (body) en formato JSON
$inputData = json_decode(file_get_contents("php://input"), true);

// Validar que se reciba un JSON válido y el campo CreditoID no esté vacío
if (!$inputData || !isset($inputData['CreditoID']) || empty(trim($inputData['CreditoID']))) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error",
        "mensaje" => "Se requiere el parámetro 'CreditoID' dentro del JSON body."
    ]);
    exit();
}

$creditoId = trim($inputData['CreditoID']);

// Instanciar conexión
$database = new Database();
$db = $database->getConnection();

// Consulta SQL con Prepared Statements
$sql = "SELECT 
            AmortizacionID AS Numero,
            FechaInicio,
            FechaVencim,
            FechaExigible,
            Estatus,
            Capital,
            Interes,
            IVAInteres,
            (Capital + Interes) AS MontoCuota,
            Capital AS CapitalVigente
        FROM AMORTICREDITO 
        WHERE CreditoID = :creditoId";

try {
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':creditoId', $creditoId, PDO::PARAM_STR);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($registros) {
        http_response_code(200); // OK
        echo json_encode([
            "status" => "success",
            "total"  => count($registros),
            "data"   => $registros
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404); // Not Found
        echo json_encode([
            "status"  => "success",
            "mensaje" => "No se encontraron registros para el CreditoID proporcionado.",
            "data"    => []
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error en la consulta: " . $e->getMessage()
    ]);
}
?>