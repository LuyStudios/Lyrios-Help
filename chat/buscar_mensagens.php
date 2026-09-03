<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json');

if (!estaLogado()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado.']); exit; }

$conversaId = (int)($_GET['conversa_id'] ?? 0);
$ultimaId = (int)($_GET['ultima_id'] ?? 0);
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("SELECT * FROM conversas WHERE id = ?");
$stmt->execute([$conversaId]);
$conversa = $stmt->fetch();

if (!utilizadorPertenceConversa($conversa, $uid)) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sem permissão.']);
    exit;
}

// Marca como lidas as mensagens recebidas do outro participante
$pdo->prepare("UPDATE mensagens_chat SET lida = 1 WHERE conversa_id = ? AND remetente_id != ? AND lida = 0")
    ->execute([$conversaId, $uid]);

$stmt = $pdo->prepare("SELECT * FROM mensagens_chat WHERE conversa_id = ? AND id > ? ORDER BY id ASC");
$stmt->execute([$conversaId, $ultimaId]);
$linhas = $stmt->fetchAll();

$mensagens = [];
foreach ($linhas as $m) {
    $mensagens[] = [
        'id' => (int)$m['id'],
        'sou_eu' => (int)$m['remetente_id'] === (int)$uid,
        'tipo' => $m['tipo'],
        'conteudo' => $m['conteudo'],
        'audio_url' => $m['tipo'] === 'audio' ? (BASE_URL . '/chat/audio.php?id=' . $m['id']) : null,
        'duracao' => $m['duracao_segundos'],
        'hora' => date('H:i', strtotime($m['criado_em'])),
    ];
}

echo json_encode(['sucesso' => true, 'mensagens' => $mensagens]);
