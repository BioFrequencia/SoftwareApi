<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

/**
 * Relatório de Alunos + Situação de Atraso
 * -----------------------------------------
 * Segue o mesmo padrão do login da coordenação: PDO, prepared statements,
 * retorno em JSON com "sucesso", "mensagem" e "dados".
 *
 * SUPOSIÇÕES sobre a tabela (ajuste os nomes das colunas se forem diferentes):
 *
 * tb_alunos
 *   id_aluno         (int)
 *   nome_aluno       (varchar)
 *   email_aluno      (varchar)
 *   turma_aluno      (varchar)
 *   horario_entrada  (time)      -> horário que o aluno registrou entrada hoje
 *
 * Horário limite de tolerância definido abaixo em HORARIO_LIMITE.
 * Se preferir, isso pode virar uma coluna configurável no banco depois.
 */

define('HORARIO_LIMITE', '08:00:00');

try {
    // Filtro opcional por turma ou nome (via GET, ex: ?busca=Turma+A)
    $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

    $sql = "SELECT id_aluno, nome_aluno, email_aluno, turma_aluno, horario_entrada
            FROM tb_alunos";

    $params = [];

    if ($busca !== '') {
        $sql .= " WHERE nome_aluno ILIKE ? OR turma_aluno ILIKE ?";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }

    $sql .= " ORDER BY nome_aluno ASC";

    $select = $conexao->prepare($sql);
    $select->execute($params);
    $alunos = $select->fetchAll(PDO::FETCH_ASSOC);

    // Monta o relatório, calculando quem chegou atrasado
    $relatorio = array_map(function ($aluno) {
        $atrasado = false;

        if (!empty($aluno['horario_entrada'])) {
            $atrasado = $aluno['horario_entrada'] > HORARIO_LIMITE;
        }

        return [
            "id"              => $aluno['id_aluno'],
            "nome"            => $aluno['nome_aluno'],
            "email"           => $aluno['email_aluno'],
            "turma"           => $aluno['turma_aluno'],
            "horario_entrada" => $aluno['horario_entrada'],
            "atrasado"        => $atrasado,
            "situacao"        => $atrasado ? "Atrasado" : "Pontual",
        ];
    }, $alunos);

    $total_atrasados = count(array_filter($relatorio, fn($a) => $a['atrasado']));

    echo json_encode([
        "sucesso"          => true,
        "mensagem"         => "Relatório gerado com sucesso",
        "total_alunos"     => count($relatorio),
        "total_atrasados"  => $total_atrasados,
        "dados"            => $relatorio
    ]);

} catch (PDOException $erro) {
    echo json_encode([
        "sucesso"  => false,
        "mensagem" => "Erro no servidor: " . $erro->getMessage(),
        "dados"    => null
    ]);
}