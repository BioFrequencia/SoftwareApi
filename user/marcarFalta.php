<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

/*
 * OBS: id_coordenacao está vindo direto do corpo da requisição porque o
 * projeto ainda não tem sessão/token de login implementado (selectCoord.php
 * só valida e devolve os dados). Quando a autenticação estiver pronta, troque
 * isso por um valor extraído da sessão/token — hoje qualquer requisição pode
 * "assinar" a falta em nome de outra coordenação.
 */

$dados = json_decode(file_get_contents("php://input"), true);

if (!isset($dados["id_aluno"], $dados["id_coordenacao"])) {
    echo json_encode(["sucesso" => false, "mensagem" => "id_aluno e id_coordenacao são obrigatórios", "dados" => null]);
    exit;
}

$idAluno       = $dados["id_aluno"];
$idCoordenacao = $dados["id_coordenacao"];
$dtaFalta      = $dados["dta_falta"] ?? null; // se não vier, o banco assume o momento atual
$motivoFalta   = $dados["motivo_falta"] ?? null;
$observacao    = $dados["observacao_falta"] ?? null;
$justificado   = (isset($dados["justificado_falta"]) && $dados["justificado_falta"]) ? 'true' : 'false';
// bindado como string 'true'/'false': PDO::PARAM_BOOL tem um bug conhecido
// no driver pgsql que costuma virar '' e quebra o insert numa coluna boolean

try {
    // Confere se o aluno existe antes de tentar gravar a falta
    $verificaAluno = $conexao->prepare("SELECT id_aluno FROM tb_aluno WHERE id_aluno = ?");
    $verificaAluno->bindParam(1, $idAluno);
    $verificaAluno->execute();

    if ($verificaAluno->rowCount() === 0) {
        echo json_encode(["sucesso" => false, "mensagem" => "Aluno não encontrado", "dados" => null]);
        exit;
    }

    $insert = $conexao->prepare("
        INSERT INTO tb_falta (id_aluno, id_coordenacao, dta_falta, motivo_falta, justificado_falta, observacao_falta)
        VALUES (?, ?, COALESCE(?, NOW()), ?, ?, ?)
        RETURNING id_falta, dta_falta
    ");
    $insert->bindParam(1, $idAluno);
    $insert->bindParam(2, $idCoordenacao);
    $insert->bindValue(3, $dtaFalta, $dtaFalta === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $insert->bindParam(4, $motivoFalta);
    $insert->bindParam(5, $justificado);
    $insert->bindParam(6, $observacao);
    $insert->execute();

    $novaFalta = $insert->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Falta registrada com sucesso",
        "dados" => [
            "id_falta" => $novaFalta["id_falta"],
            "dta_falta" => $novaFalta["dta_falta"]
        ]
    ]);

} catch (PDOException $erro) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao registrar falta: " . $erro->getMessage(), "dados" => null]);
}
?>