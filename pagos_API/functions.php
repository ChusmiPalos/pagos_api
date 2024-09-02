<?php
// Incluimos la configuración de la bbdd
require_once 'config.php';
global $conn;

/*
 $data solo debe contener 3 piezas
    Si es pago por tarjeta las piezas son: 'amount', 'currency' y 'card_num'
    Pero si es en metálico: 'amount', 'currency' y 'coin_types'
 */
function verificar_data($data)
{
    global $conn;
    $verif_data = true;
    // Primer paso, espero recibir 3 piezas tanto en pago con tarjeta como en metálico
    if (sizeof($data) != 3) {
        $verif_data = false;
    } else {
        // amount, y currency, son piezas que vienen siempre
        if ($verif_data && isset($data['amount'])) {
            $verif_data = verificar_amount($data['amount']);
        }
        if ($verif_data && isset($data['currency'])) {
            $verif_data = verificar_currency($data['currency'], $conn);
        }
        // card_num, solo viene en pago con tarjeta
        if ($verif_data && isset($data['card_num'])) {
            $verif_data = verificar_card_num($data['card_num']);
        }
        // coin_types, solo viene en pago en metálico
        if ($verif_data && isset($data['coin_types'])) {
            $verif_data = verificar_coin_types($data['coin_types']);
        }
    }
    // Si alguna de las piezas, no supera su validación específica, la validación general de data habrá fallado
    return $verif_data;
}

// Función para validar el campo "amount"
function verificar_amount($amount)
{
    if (!is_numeric($amount) || $amount <= 0) {
        return false;
    }
    return true;
}

// Función para validar el campo "currency"
function verificar_currency($currency, $conn)
{
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

function verificar_coin_types($coin_types)
{
    if (gettype($coin_types) != 'array') {
        return false;
    }
    return true;
}

// Función para obtener el tipo de cambio de la moneda
function obtener_tipo_cambio($currency, $pdo)
{
    $stmt = $pdo->prepare("SELECT exchange FROM currencies WHERE coin_name = :currency LIMIT 1");
    $stmt->execute(['currency' => strtolower($currency)]);
    $exchange = $stmt->fetchColumn();

    return $exchange;
}
