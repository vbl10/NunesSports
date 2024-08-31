CREATE DATABASE IF NOT EXISTS nunes_sports;

USE nunes_sports;

CREATE TABLE IF NOT EXISTS products 
(
    codigo INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nome VARCHAR(30),
    descricao VARCHAR(200),
    preco DECIMAL(15, 2)
);
