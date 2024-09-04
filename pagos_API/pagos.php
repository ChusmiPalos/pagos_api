<?php


function ingresar_pago($data)
{
    // Como nuestra API va a admitir otras divisas, transformamos la cantidad a pagar en la divisa usada por el módulo de cobro a euros
    global $conn;
    $amount_euros = transformar_a_euros($data['amount'], $data['currency'], $conn);

    if (isset($data['card_num'])) {
        $respuesta = pago_tarjeta($data, $amount_euros, $conn);
    }
    if (isset($data['coin_types'])) {
        $respuesta = pago_metalico($data, $amount_euros, $conn);
    }
    return $respuesta;
}

function pago_tarjeta($data, $amount_euros, $conn)
{

    /* Aquí es donde enviaríamos el pago al Banco, puede haberse realizado correctamente o puede que no.
        Según la documentación de la prueba, si el pago es correcto, la aplicación del banco devolverá:
        {"success": true}

        Y si el pago no se ha podido realizar:
        {"success": false, "error": 702}

        Aquí, para simular la respuesta del banco, aplicamos un número aleatorio que puede ser 0 o 1, como verdadero o falso
    */

    if (rand(0, 1)) {
        // Si la respuesta ha sido afirmativa, guardamos el pago en la tabla "registro_pagos"
        $resp = registrar_pago($data['amount'],$amount_euros, $data['currency'],'card',$conn);
        
    } else {
        $resp = ["success" => false, "error" => 702];
    }
    return $resp;
}
function pago_metalico($data, $amount_euros, $conn)
{

    // En este punto, ya hemos verificado las monedas, con lo cual, sabemos que son todas de curso legal, debemos saber la cantidad que el usuario ha introducido en el módulo de pago, y compararlo con la cantidad que queremos cobrar, para calcular el cambio
    $total_ingresado = 0;
    foreach ($data['coin_types'] as $key => $value) {
        $total_ingresado += $key * $value;
    }
    if ($total_ingresado < $data['amount']) {
        $resp = ["success" => false, "error" => "Dinero ingresado insuficiente para afrontar el pago"];
    } else {
        $cambio = $total_ingresado - $data['amount'];
        // $cambio_restante es la variable que vamos a llevar hasta 0 para calcular las monedas a entregar al cliente
        $cambio_restante = $cambio;

        // Preparar la consulta para obtener las monedas válidas para la divisa proporcionada
        $query = "SELECT coins FROM currencies WHERE coin_name = :currency";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':currency', $data['currency'], PDO::PARAM_STR);

        // Ejecutar la consulta
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convertir los valores de monedas válidas a un array de PHP
        $valid_coins = explode(',', $result['coins']);

        foreach ($valid_coins as $moneda) {
            // Calculamos cuántas monedas de este valor podemos devolver
            $cantidad = intval($cambio_restante / $moneda);
            // Si necesito una cantidad positiva de esa moneda en concreto, lo almaceno en el array $coin_types, donde la $key es el valor de la moneda, y el $value, la cantidad de monedas
            if ($cantidad > 0) {
                $coin_types[$moneda] = $cantidad;
                // Reducimos el cambio en el valor correspondiente
                $cambio_restante -= $cantidad * $moneda;
            }
        }
        $resp = registrar_pago($data['amount'],$amount_euros, $data['currency'],'cash',$conn);
        $resp['amount']=$cambio;
        $resp['coin_types']=$coin_types;
    }
    return $resp;
}

function transformar_a_euros($amount, $currency, $conn)
{
    $exchange = obtener_tipo_cambio($currency, $conn);
    // obtenemos la cantidad a abonar en euros, redondeando a 0 decimales, porque al igual que con los euros, en otras divisas tomamos como cantidad mínima 1 centimo.
    return round($amount * $exchange, 0, true);
}

// Función para obtener el tipo de cambio de la moneda
function obtener_tipo_cambio($currency, $conn)
{
    $stmt = $conn->prepare("SELECT exchange FROM currencies WHERE coin_name = :currency LIMIT 1");
    $stmt->execute(['currency' => strtolower($currency)]);
    $exchange = $stmt->fetchColumn();

    return $exchange;
}

function registrar_pago($amount_original, $amount_euros, $currency_original, $pay_type,$conn){
    try {
        // Preparar la consulta SQL con placeholders
        $sql = "INSERT INTO registro_pagos (amount_original, amount_eur, currency_original, pay_type) 
                VALUES (:amount_original, :amount_eur, :currency_original, :pay_type)";
        // Preparar la consulta
        $stmt = $conn->prepare($sql);

        // Asociamos cada parámetro con la variable que lo contiene (así evitamos inyecciones de SQL)
        $stmt->bindParam(':amount_original', $amount_original, PDO::PARAM_INT);
        $stmt->bindParam(':amount_eur', $amount_euros, PDO::PARAM_INT);
        $stmt->bindParam(':currency_original', $currency_original, PDO::PARAM_STR);
        $stmt->bindParam(':pay_type', $pay_type, PDO::PARAM_STR);

        // Ejecutar la consulta
        $stmt->execute();

        $resp = ["success" => true];
    } catch (PDOException $e) {
        // En este caso hemos recibido confirmación del cobro por el banco, pero ha fallado el registro en la web
        $resp = ["success" => true, "error" => $e->getMessage()];
    }
    return $resp;
}
