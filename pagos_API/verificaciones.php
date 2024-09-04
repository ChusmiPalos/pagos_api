<?php

/*
 $data solo debe contener 3 piezas
    Si es pago por tarjeta las piezas son: 'amount', 'currency' y 'card_num'
    Pero si es en metálico: 'amount', 'currency' y 'coin_types'
 */
function verificar_data($data)
{
    // $conn es la conexión con la bbdd establecida en config.php
    global $conn;

    // Suponemos que la verificación de todas las piezas va a ser válida
    $supera_verificacion = true;
    // Primer paso, espero recibir 3 piezas tanto en pago con tarjeta como en metálico
    if (sizeof($data) != 3) {
        $supera_verificacion = false;
    } else {
        // amount, y currency, son piezas que vienen siempre
        if ($supera_verificacion && isset($data['amount'])) {
            $supera_verificacion = verificar_amount($data['amount']);
        }

        if ($supera_verificacion && isset($data['currency'])) {
            $supera_verificacion = verificar_currency($data['currency'], $conn);
        }

        // card_num, solo viene en pago con tarjeta
        if ($supera_verificacion && isset($data['card_num'])) {
            $supera_verificacion = verificar_card_num($data['card_num']);
        }

        // coin_types, solo viene en pago en metálico
        if ($supera_verificacion && isset($data['coin_types'])) {
            $supera_verificacion = verificar_coin_types($data['coin_types'], $data['currency'], $conn);
        }
    }
    // Si alguna de las piezas, no supera su validación específica, la validación general de data habrá fallado
    return $supera_verificacion;
}

// Función para validar el campo "amount"
function verificar_amount($amount)
{
    // Debe ser numérico y positivo
    return (is_numeric($amount) && $amount >= 0);
}

// Función para validar el campo "currency"
function verificar_currency($currency, $conn)
{
    // $currency debe ser una moneda admitida en la tabla "currencies"
    $stmt = $conn->prepare("SELECT COUNT(*) FROM currencies WHERE coin_name = :currency");
    $stmt->execute(['currency' => strtolower($currency)]);

    $count = $stmt->fetchColumn();

    return $count > 0;
}

// Función para validar el campo "card_num" (si está presente)
function verificar_card_num($card_num)
{
    if (!is_numeric($card_num) || strlen($card_num) != 16) {
        return false;
    } else {
        // Algoritmo de Luhn (He de decir que el algoritmo de Luhn no lo conocía y lo he tenido que buscar)
        $number = strrev($card_num);
        $sum = 0;

        for ($i = 0; $i < strlen($number); $i++) {
            $digit = (int)$number[$i];

            if ($i % 2 == 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        return $sum % 10 === 0;
    }
}

function verificar_coin_types($coin_types, $currency, $conn)
{
    // Si $coin_types no es un array, retornamos false
    if (gettype($coin_types) != 'array') {
        return false;
    }
    // Preparar la consulta para obtener las monedas válidas para la divisa proporcionada
    $query = "SELECT coins FROM currencies WHERE coin_name = :currency";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':currency', $currency, PDO::PARAM_STR);

    // Ejecutar la consulta
    $stmt->execute();

    // Obtener el resultado
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encontró la divisa, retornamos false
    if (!$result) {
        return false;
    }

    // Convertir los valores de monedas válidas a un array de PHP
    $valid_coins = explode(',', $result['coins']);

    // Verificar que todos los tipos de monedas entregados son válidos
    foreach ($coin_types as $coin_value => $coin_quantity) {
        if (!in_array($coin_value, $valid_coins)) {
            // Si alguna moneda no es válida, retornamos false
            return false;
        }
    }

    // Si todas las monedas son válidas, retornamos true
    return true;
}
