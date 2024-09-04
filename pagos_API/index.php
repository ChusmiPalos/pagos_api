<?php
// Incluimos los archivos que intervienen en el funcionamiento de la API

// Hemos compartimentado el código en la sección de verificaciones
require_once 'verificaciones.php';

// Y una vez está todo verificado, registramos el pago
require_once 'pagos.php';

// Incluimos la configuración de la bbdd
require_once 'config.php';

// Establecer los encabezados para permitir solicitudes desde cualquier origen y manejar JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

/*  Verificar que la solicitud sea de tipo POST
    Nos estamos limitando al tipo POST porque en esta API deberíamos recibir peticiones de pago, ya sea por tarjeta o en metálico
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(["error" => "Metodo no permitido. Usa POST."]);
    exit;
}

// Obtenemos el contenido bruto de la solicitud
$input = file_get_contents("php://input");

// Y decodificamos el JSON recibido
$data = json_decode($input, true);

/*  Verificar si la decodificación fue exitosa
    Tomo como campos obligatorios los de "amount" y "currency", y "card_num" en pago con tarjeta, y "coin_types" en pago en metálico
*/

if (
    json_last_error() == JSON_ERROR_NONE
    && verificar_data($data)
) {
    http_response_code(200);
    echo json_encode(ingresar_pago($data));
    exit;
} else {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["error" => "Datos JSON inválidos o falta algún campo obligatorio ('amount', 'currency'), en pago con tarjeta 'card_num', o bien 'coin_types' en pago en metálico."]);
    exit;
}
