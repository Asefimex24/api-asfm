<?php
// Configuración de encabezados para respuesta JSON y soporte CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Incluir el archivo de conexión a la base de datos
require_once '../config/database.php';

try {
    // 2. Instanciar la clase Database y obtener la conexión PDO
    $database = new Database();
    $db = $database->getConnection();

    // 3. Obtener el cuerpo de la petición (BODY JSON)
    $input = json_decode(file_get_contents('php://input'), true);

    $clienteID   = $input['ClienteID'] ?? null;
    $direccionID = $input['DireccionID'] ?? null;

    // 4. Validar parámetros requeridos
    if (empty($clienteID) || empty($direccionID)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Los campos ClienteID y DireccionID son requeridos.'
        ]);
        exit;
    }

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
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    // 7. Retornar respuesta según si se encontró o no el registro
    if ($cliente) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data'   => $cliente
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