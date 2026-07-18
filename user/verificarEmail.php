<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

$dados = json_decode(file_get_contents("php://input"), true);

$email = $dados["email"];

try{
$select = $conexao->prepare("SELECT email_coordenacao FROM tb_coordenacao WHERE email_coordenacao = ?");
$select->bindParam(1, $email);
$select->execute();
$coordenacao = $select->fetch(PDO::FETCH_ASSOC);

echo json_encode(["sucesso" => true, "mensagem" => $coordenacao ? "Email já cadastrado" : "Disponível", "dados" => $coordenacao ?: null]);
}catch(Exception $ex){
    echo json_encode(["sucesso" =>false, "mensagem" =>"Erro da api " , $ex->getMessage(), "dados" =>null]);  
}?>