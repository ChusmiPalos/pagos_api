<?php
// Datos de conexión a la base de datos
$host = 'localhost';
$db_name = 'pagos';
$username = 'root';
$password = '';

try {
    // Crear una nueva conexión PDO
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Establecer el modo de errores de PDO a excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Errores de conexión
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
