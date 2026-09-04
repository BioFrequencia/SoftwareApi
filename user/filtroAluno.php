<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/conexao.php';

define('DIAS_LETIVOS_MES', 20); // mesma constante usada em coutAlunos.php

function calcularStatus($frequencia) {
    if ($frequencia < 85) return "Em risco";
    if ($frequencia < 90) return "Atenção";
    if ($frequencia < 98) return "Regular";
    return "Excelente";
}

$dados = json_decode(file_get_contents("php://input"), true) ?? [];

$busca      = $dados["busca"] ?? null;          // nome ou id_aluno
$idSala     = $dados["id_sala"] ?? null;         // filtro de turma
$status     = $dados["status"] ?? null;          // filtro de status (aplicado após calcular)
$pagina     = max(1, (int)($dados["pagina"] ?? 1));
$porPagina  = max(1, (int)($dados["por_pagina"] ?? 10));

try {
    $primeiroDiaMes = date('Y-m-01');

    $sql = "
        SELECT al.id_aluno, al.nome_aluno, s.nome_sala,
               COUNT(a.id_atraso) AS qtd_atrasos
        FROM tb_aluno al
        LEFT JOIN tb_sala s ON s.id_sala = al.id_sala
        LEFT JOIN tb_atraso a ON a.id_aluno = al.id_aluno AND a.dta_atraso >= ?
        WHERE 1 = 1
    ";
    $params = [$primeiroDiaMes];

    if (!empty($busca)) {
        $sql .= " AND (al.nome_aluno ILIKE ? OR al.id_aluno::text = ?)";
        $params[] = "%$busca%";
        $params[] = $busca;
    }

    if (!empty($idSala)) {
        $sql .= " AND al.id_sala = ?";
        $params[] = $idSala;
    }

    $sql .= " GROUP BY al.id_aluno, al.nome_aluno, s.nome_sala ORDER BY al.nome_aluno";

    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcula faltas/atrasos/frequência/status em PHP (mesma lógica do dashboard)
    $listaCompleta = [];
    foreach ($alunos as $aluno) {
        $qtdAtrasos = (int)$aluno["qtd_atrasos"];
        $frequencia = max(0, round(100 - (($qtdAtrasos / DIAS_LETIVOS_MES) * 100)));
        $statusAluno = calcularStatus($frequencia);

        if ($status !== null && $statusAluno !== $status) {
            continue; // aplica filtro de status
        }

        $listaCompleta[] = [
            "id_aluno" => $aluno["id_aluno"],
            "nome_aluno" => $aluno["nome_aluno"],
            "turma" => $aluno["nome_sala"],
            "faltas" => $qtdAtrasos,
            "atrasos" => $qtdAtrasos,
            "frequencia" => $frequencia . "%",
            "status" => $statusAluno
        ];
    }

    // Paginação feita em PHP, pois o status é calculado após a query
    $totalRegistros = count($listaCompleta);
    $totalPaginas = (int)ceil($totalRegistros / $porPagina);
    $offset = ($pagina - 1) * $porPagina;
    $paginaAtual = array_slice($listaCompleta, $offset, $porPagina);

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Alunos carregados com sucesso",
        "dados" => [
            "alunos" => $paginaAtual,
            "pagina_atual" => $pagina,
            "total_paginas" => $totalPaginas,
            "total_registros" => $totalRegistros
        ]
    ]);

} catch (PDOException $erro) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao buscar alunos: " . $erro->getMessage(), "dados" => null]);
}
?>