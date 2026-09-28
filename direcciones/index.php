<?php
// Encabezados HTTP
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo del preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'POST'){
    
        $inputData = json_decode(file_get_contents("php://input"), true);

    if (!$inputData || !isset($inputData['ClienteID']) || empty(trim($inputData['ClienteID']))) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "mensaje" => "Se requiere el parámetro 'ClienteID' dentro del JSON body."
        ]);
        exit();
    }

    $clienteId = trim($inputData['ClienteID']);
    $direccionID= trim($inputData['DireccionID']);

try{

    // 5. Consulta SQL con el JOIN corregido
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

    // 6. Preparar y ejecutar la consulta (previene SQL Injection)
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':clienteID'   => $clienteID,
        ':direccionID' => $direccionID
    ]);

    // Obtener los datos como un arreglo asociativo
    $direccion = $stmt->fetch(PDO::FETCH_ASSOC);

    // 7. Retornar respuesta según si se encontró o no el registro
    if ($direccion) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data'   => $direccion
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404); // Not Found
        echo json_encode([
            'status'  => 'error',
            'mensaje' => 'No se encontró la dirección asociada al cliente especificado.'
        ]);
    }

} catch (PDOException $e) {
    // Manejo de excepciones en las consultas SQL
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'mensaje' => 'Error al consultar la base de datos: ' . $e->getMessage()
    ]);
}

}