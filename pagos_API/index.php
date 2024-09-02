<?php
// Incluimos el archivo functions.php que va a contener las funciones que intervienen en el funcionamiento de la API
require_once 'functions.php';

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

// Obtener el contenido bruto de la solicitud
$input = file_get_contents("php://input");

// Decodificar el JSON recibido
$data = json_decode($input, true);

/*  Verificar si la decodificación fue exitosa
    Tomo como campos obligatorios los de "amount" y "currency", y "card_num" en pago con tarjeta, y "coin_types" en pago en metálico
*/
if (
    json_last_error() == JSON_ERROR_NONE
    && verificar_data($data)
) {
    echo 'Pago válido. De aquí pasamos a registrar el pago';
    exit;
    // if (!verificar_amount($data['amount'])) {
    //     http_response_code(400); // Solicitud incorrecta
    //     echo json_encode(["error" => "El campo 'amount' debe ser un número positivo."]);
    //     exit;
    // }
    // echo json_encode(["respuesta" => 'valores_validos', "data" => $data]);
    // exit;
} else {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["error" => "Datos JSON inválidos o falta algún campo obligatorio ('amount', 'currency'), en pago con tarjeta 'card_num', o bien 'coin_types' en pago en metálico."]);
    exit;
}

// Obtener el valor enviado
$receivedValue = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

// Verificar si el valor es booleano
if (is_null($receivedValue)) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["error" => "El campo 'value' debe ser booleano (true o false)."]);
    exit;
}

// Calcular el valor contrario
$responseValue = !$receivedValue;

// Responder con el valor contrario
echo json_encode(["value" => $responseValue]);
