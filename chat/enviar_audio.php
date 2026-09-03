<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json');

if (!estaLogado()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado.']); exit; }
csrfVerificar();

$conversaId = (int)($_POST['conversa_id'] ?? 0);
$duracao = (int)($_POST['duracao'] ?? 0);
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("SELECT * FROM conversas WHERE id = ?");
$stmt->execute([$conversaId]);
$conversa = $stmt->fetch();

if (!utilizadorPertenceConversa($conversa, $uid)) {
    registarLogSeguranca($pdo, 'acesso_negado_chat', $uid, "Tentativa de envio de áudio para conversa #$conversaId sem permissão.");
    http_response_code(403);
    echo json_encode(['erro' => 'Sem permissão.']);
    exit;
}

$res = uploadAudioChat('audio');
if ($res['erro'] || !$res['sucesso']) {
    echo json_encode(['erro' => $res['erro'] ?: 'Falha ao enviar o áudio.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO mensagens_chat (conversa_id, remetente_id, tipo, ficheiro_audio, duracao_segundos) VALUES (?,?,'audio',?,?)");
$stmt->execute([$conversaId, $uid, $res['caminho'], $duracao]);
$novoId = $pdo->lastInsertId();

echo json_encode(['sucesso' => true, 'id' => $novoId, 'criado_em' => date('H:i')]);
