<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

$dados = json_decode(file_get_contents("php://input"), true);

/*$aluno  = $dados["aluno"];
$id_sala = $dados["id_sala"];
$dta_nasc_aluno = $dados["dta_nasc_aluno"];
$genero_aluno = $dados["genero_aluno"];*/

$aluno  = "Klebersonzinho";
$id_sala = "5";
$dta_nasc_aluno = "2009/01/12";
$genero_aluno = "Masculino";

try {

    $insert = $conexao->prepare("INSERT INTO tb_aluno(nome_aluno,id_sala,dta_nasc_aluno,genero_aluno) VALUES(?,?,?,?)");
    $insert->bindParam(1, $aluno);
    $insert->bindParam(2, $id_sala);
    $insert->bindParam(3, $dta_nasc_aluno);
    $insert->bindParam(4, $genero_aluno);
    $insert->execute();
    
    echo json_encode(["sucesso" => true, "mensagem" => "Aluno cadastrado com sucesso", "dados" => null]);
} catch (PDOException $erro) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao cadastrar: " . $erro->getMessage(), "dados" => null]);
}
?>