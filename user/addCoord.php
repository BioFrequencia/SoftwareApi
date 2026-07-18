<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

$dados = json_decode(file_get_contents("php://input"), true);

$nome  = $dados["nome"];
$email = $dados["email"];
$senha = $dados["senha"];

try {

    $insert = $conexao->prepare("INSERT INTO tb_coordenacao(nome_coordenacao,email_coordenacao,senha_coordenacao) VALUES(?,?,?)");
    $insert->bindParam(1, $nome);
    $insert->bindParam(2, $email);
    $insert->bindParam(3, $senha);
    $insert->execute();
    
    echo json_encode(["sucesso" => true, "mensagem" => "Usuário cadastrado com sucesso", "dados" => null]);
} catch (PDOException $erro) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao cadastrar: " . $erro->getMessage(), "dados" => null]);
}
?>