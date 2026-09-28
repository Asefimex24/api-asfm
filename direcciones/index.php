<?php
// Activar reporte de errores durante desarrollo (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Encabezados HTTP
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo del preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Incluir configuración de la base de datos (Subiendo 3 niveles)
$configPath = dirname(__DIR__, 3) . '/config/database.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "mensaje" => "No se encontró el archivo de conexión en: " . $configPath
    ]);
    exit();
}

require_once $configPath;

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 2. Verificar que sea una petición POST
if ($method === 'POST') {

    // Leer el payload JSON recibido
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true);

    // Validar existencia y que no vengan vacíos ClienteID ni DireccionID
    if (
        !is_array($inputData) ||
        !isset($inputData['ClienteID']) || empty(trim($inputData['ClienteID'])) ||
        !isset($inputData['DireccionID']) || empty(trim($inputData['DireccionID']))
    ) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "mensaje" => "Se requieren los parámetros 'ClienteID' y 'DireccionID' en el cuerpo JSON de la petición."
        ]);
        exit();
    }

    $clienteID = trim($inputData['ClienteID']);
    $direccionID = trim($inputData['DireccionID']);

    try {
        // Consulta SQL con el JOIN corregido
        $sql = "SELECT
                    a.ClienteID,
                    b.NombreCompleto,
                    a.DireccionID,
                    a.TipoDireccionID,
                    a.PaisID AS ClavePais,
                    c.Nombre AS Pais,
                    a.EstadoId AS ClaveEntidad,
                    d.Nombre AS EntidadFederativa,
                    a.MunicipioID AS ClaveMunicipio,
                    e.Nombre AS Municipio           
                FROM DIRECCLIENTE a
                INNER JOIN CLIENTES b ON a.ClienteID = b.ClienteID
                INNER JOIN PAISES c ON a.PaisID = c.PaisID
                INNER JOIN ESTADOSREPUB d ON a.EstadoID = d.EstadoID
                LEFT JOIN MUNICIPIOSREPUB e ON e.MunicipioID = a.MunicipioID AND e.EstadoID = a.EstadoID
                WHERE a.ClienteID = :clienteID 
                  AND a.DireccionID = :direccionID";

        // Preparar y ejecutar la consulta
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':clienteID'   => $clienteID,
            ':direccionID' => $direccionID
        ]);

        $direccion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($direccion) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data'   => $direccion
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'mensaje' => 'No se encontró la dirección asociada al cliente especificado.'
            ]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'mensaje' => 'Error al consultar la base de datos: ' . $e->getMessage()
        ]);
    }

} else {
    // Si envían una petición GET u otro método distinto a POST
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status'  => 'error',
        'mensaje' => 'Método no permitido. Utiliza POST.'
    ]);
}