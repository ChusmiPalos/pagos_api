-- Si se ejecuta mediante el terminal:
-- mysql -u root -p < C:\xampp\htdocs\pagos_api\configuracion_bd.sql

-- Crear la base de datos 'pagos' si no existe
CREATE DATABASE IF NOT EXISTS pagos;

-- Usar la base de datos 'pagos'
USE pagos;

-- Crear la tabla 'currencies' si no existe
CREATE TABLE IF NOT EXISTS currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coin_name VARCHAR(10) NOT NULL UNIQUE,
    exchange FLOAT NOT NULL,
    coins VARCHAR(100)
);


-- Insertar registros iniciales en la tabla 'currencies'
INSERT INTO currencies (coin_name, exchange, coins) VALUES ('eur', 1,'1,2,5,10,20,50,100,200,500,1000,2000,5000,10000,20000,50000')
    ON DUPLICATE KEY UPDATE exchange = VALUES(exchange);

INSERT INTO currencies (coin_name, exchange, coins) VALUES ('usd', 0.92, '1,5,10,25,50,100,200,500,1000,2000,5000,10000')
    ON DUPLICATE KEY UPDATE exchange = VALUES(exchange);

INSERT INTO currencies (coin_name, exchange, coins) VALUES ('gbp', 0.85,'1,5,10,25,50,100,200,500,1000,2000,5000,10000')
    ON DUPLICATE KEY UPDATE exchange = VALUES(exchange);


CREATE TABLE IF NOT EXISTS registro_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount_eur INT NOT NULL,
    amount_original INT NOT NULL,
    currency_original VARCHAR(10),
    pay_type VARCHAR(10)
)