<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';
require_once __DIR__ . '/../Authentication/autenticar.php';

/**
 * Marca atraso de um aluno em uma data específica.
 * -------------------------------------------------
 * POST /marcarAtraso.php
 * body: { "id_aluno": 12, "data": "2026-09-03" }   -> "data" é opcional, default = hoje
 *
 * Regra: se já existir um registro de frequência para esse aluno nesse dia
 * (presença, falta...), ele é SOBRESCRITO para 'atraso' (upsert via
 * ON CONFLICT). Requer a constraint UNIQUE (id_aluno, data_registro) em
 * tb_registro_frequencia — veja o ALTER TABLE que passei antes deste arquivo.
 */

$idCoordenacaoLogada = autenticar();

$dados = json_decode(file_get_contents("php://input"), true);

$idAluno      = $dados['id_aluno'] ?? null;
$dataRegistro = $dados['data'] ?? date('Y-m-d');

// ---- Validações de entrada ----
if (empty($idAluno) || !is_numeric($idAluno)) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "mensagem" => "id_aluno é obrigatório e deve ser numérico", "dados" => null]);
    exit;
}

$dataValida = DateTime::createFromFormat('Y-m-d', $dataRegistro);
if (!$dataValida || $dataValida->format('Y-m-d') !== $dataRegistro) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "mensagem" => "Data inválida, use o formato AAAA-MM-DD", "dados" => null]);
    exit;
}

$hoje = new DateTime('today');
if ($dataValida > $hoje) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "mensagem" => "Não é possível marcar atraso em data futura", "dados" => null]);
    exit;
}

try {
    // ---- Confirma que o aluno existe antes de inserir ----
    $verifica = $conexao->prepare("SELECT id_aluno, nome_aluno FROM tb_aluno WHERE id_aluno = ?");
    $verifica->bindParam(1, $idAluno);
    $verifica->execute();
    $aluno = $verifica->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        http_response_code(404);
        echo json_encode(["sucesso" => false, "mensagem" => "Aluno não encontrado", "dados" => null]);
        exit;
    }

    // ---- Upsert: insere ou, se já existir registro nesse dia, atualiza para 'atraso' ----
    $sql = "
        INSERT INTO tb_registro_frequencia (id_aluno, data_registro, tipo)
        VALUES (?, ?, 'atraso')
        ON CONFLICT (id_aluno, data_registro)
        DO UPDATE SET tipo = 'atraso'
        RETURNING id_registro, id_aluno, data_registro, tipo
    ";

    $insert = $conexao->prepare($sql);
    $insert->bindParam(1, $idAluno);
    $insert->bindParam(2, $dataRegistro);
    $insert->execute();
    $registro = $insert->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "sucesso"  => true,
        "mensagem" => "Atraso registrado com sucesso para " . $aluno['nome_aluno'],
        "dados"    => [
            "id_registro" => (int)$registro['id_registro'],
            "id_aluno"    => (int)$registro['id_aluno'],
            "nome_aluno"  => $aluno['nome_aluno'],
            "data"        => $registro['data_registro'],
            "tipo"        => $registro['tipo'],
        ]
    ]);

} catch (PDOException $erro) {
    http_response_code(500);
    echo json_encode([
        "sucesso"  => false,
        "mensagem" => "Erro no servidor: " . $erro->getMessage(),
        "dados"    => null
    ]);
}
?>