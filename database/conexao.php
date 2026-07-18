<?php

require_once __DIR__ . '/../config/config.php';

try{
    $conexao = new PDO('mysql:host=localhost; dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexao->exec('set names utf8');
    
}
catch(PDOException $erro){
  header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de conexão: " . $erro->getMessage()]);
    exit;
}
?>