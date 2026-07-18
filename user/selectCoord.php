<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

$dados = json_decode(file_get_contents("php://input"), true);

$email = $dados["email"];
$senha = $dados["senha"];
try {
    $select = $conexao->prepare("SELECT nome_coordenacao, email_coordenacao FROM tb_coordenacao WHERE email_coordenacao = ? AND senha_coordenacao = ?");
    $select->bindParam(1, $email);
    $select->bindParam(2, $senha);
    $select->execute();
    $coordenacao = $select->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["sucesso" => true, "mensagem" => $coordenacao ? "Login ok" : "Email ou senha incorretos", "dados" => $coordenacao ?: null]);
} catch (PDOException $erro) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro no servidor: " . $erro->getMessage(), "dados" => null]);
}
?>