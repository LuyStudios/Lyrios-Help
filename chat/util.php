<?php
/** Verifica se existe pelo menos uma consulta entre este paciente e este psicólogo (protege a privacidade do chat) */
function existeRelacaoConsulta($pdo, $pacienteId, $psicologoId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE paciente_id = ? AND psicologo_id = ?");
    $stmt->execute([$pacienteId, $psicologoId]);
    return $stmt->fetch()['c'] > 0;
}

/** Devolve o id da conversa entre este paciente e psicólogo, criando-a se ainda não existir */
function obterOuCriarConversa($pdo, $pacienteId, $psicologoId) {
    $stmt = $pdo->prepare("SELECT id FROM conversas WHERE paciente_id = ? AND psicologo_id = ?");
    $stmt->execute([$pacienteId, $psicologoId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    $stmt = $pdo->prepare("INSERT INTO conversas (paciente_id, psicologo_id, sala_codigo) VALUES (?,?,?)");
    $stmt->execute([$pacienteId, $psicologoId, gerarCodigoSala()]);
    return (int)$pdo->lastInsertId();
}

/** Confirma que o utilizador da sessão atual pertence mesmo a esta conversa */
function utilizadorPertenceConversa($conversa, $utilizadorId) {
    return $conversa && ((int)$conversa['paciente_id'] === (int)$utilizadorId || (int)$conversa['psicologo_id'] === (int)$utilizadorId);
}
