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

// -------------------------------------------------------------------
// OPCIÓN 1: Método GET (Obtener TODOS los clientes)
// -------------------------------------------------------------------
if ($method === 'GET') {
    $sql = "SELECT 
                a.NombreCompleto,
                a.ClienteID, 
                a.TipoPersona,
                c.NombrePromotor,
                b.NombreSucurs AS Sucursal,
                a.FechaAlta,
                a.NivelRiesgo,
                a.Curp, 
                a.Rfc,
                a.Sexo,
                a.FechaNAcimiento,
                a.Nacion as Nacionalidad,
                d.Nombre as Pais,
                e.Numidentific as Identificacion,
                a.EstadoCivil,
                a.Telefono,
                a.Correo, 
                f.Descripcion as ActividadBMX,
                g.Descripcion as ActividadINEGI,
                h.Descripcion  as SectorEcoINEGI,
                i.Descripcion  as Ocupacion,
                a.Puesto as Puesto,
                a.RazonSocial,                
                a.Estatus 
            FROM CLIENTES a
            INNER JOIN SUCURSALES b ON a.SucursalOrigen = b.SucursalID 
            INNER JOIN PROMOTORES c ON a.PromotorActual = c.PromotorID
            INNER JOIN PAISES d ON a.PaisNacionalidad = d.PaisID
            INNER JOIN IDENTIFICLIENTE e ON a.ClienteID = e.ClienteID
            INNER JOIN  ACTIVIDADESBMX f on a.ActividadBancoMX =f.ActividadBMXID
            INNER JOIN  ACTIVIDADESINEGI g on a.ActividadINEGI =g.ActividadINEGIID
            INNER JOIN SECTORESECONOM h on a.SectorEconomico =h.SectorEcoID
            INNER JOIN OCUPACIONES i on a.OcupacionID =i.OcupacionID
            ORDER BY a.ClienteID ASC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "total"  => count($clientes),
            "data"   => $clientes
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "mensaje" => "Error en la consulta: " . $e->getMessage()
        ]);
    }
    exit();
}

// -------------------------------------------------------------------
// OPCIÓN 2: Método POST (Buscar UN cliente por ClienteID en el JSON Body)
// -------------------------------------------------------------------
if ($method === 'POST') {
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

    $sql = "SELECT 
                a.NombreCompleto,
                a.ClienteID, 
                a.TipoPersona,
                c.NombrePromotor,
                b.NombreSucurs AS Sucursal,
                a.FechaAlta,
                a.NivelRiesgo,
                a.Curp, 
                a.Rfc,
                a.Sexo,
                a.FechaNAcimiento,
                a.Nacion as Nacionalidad,
                d.Nombre as Pais,
                e.Numidentific as Identificacion,
                a.EstadoCivil,
                a.Telefono,
                a.Correo, 
                f.Descripcion as ActividadBMX,
                g.Descripcion as ActividadINEGI,
                h.Descripcion  as SectorEcoINEGI,
                i.Descripcion  as Ocupacion,
                a.Puesto as Puesto,
                a.RazonSocial,                
                a.Estatus 
            FROM CLIENTES a
            INNER JOIN SUCURSALES b ON a.SucursalOrigen = b.SucursalID 
            INNER JOIN PROMOTORES c ON a.PromotorActual = c.PromotorID
            INNER JOIN PAISES d ON a.PaisNacionalidad = d.PaisID
            INNER JOIN IDENTIFICLIENTE e ON a.ClienteID = e.ClienteID
            INNER JOIN  ACTIVIDADESBMX f on a.ActividadBancoMX =f.ActividadBMXID
            INNER JOIN  ACTIVIDADESINEGI g on a.ActividadINEGI =g.ActividadINEGIID
            INNER JOIN SECTORESECONOM h on a.SectorEconomico =h.SectorEcoID
            INNER JOIN OCUPACIONES i on a.OcupacionID =i.OcupacionID 
            WHERE a.ClienteID = :clienteId";

    try {
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':clienteId', $clienteId, PDO::PARAM_STR);
        $stmt->execute();

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "data"   => $cliente
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                "status"  => "error",
                "mensaje" => "No se encontró ningún cliente con el ClienteID proporcionado.",
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
    exit();
}

// Método no permitido
http_response_code(405);
echo json_encode([
    "status"  => "error",
    "mensaje" => "Método no permitido. Utiliza GET o POST."
]);
?>
