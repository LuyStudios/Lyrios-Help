<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json');

if (!estaLogado()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado.']); exit; }
csrfVerificar();

$conversaId = (int)($_POST['conversa_id'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');
$uid = $_SESSION['utilizador_id'];

if ($mensagem === '') { echo json_encode(['erro' => 'Mensagem vazia.']); exit; }
if (mb_strlen($mensagem) > 2000) { $mensagem = mb_substr($mensagem, 0, 2000); }

$stmt = $pdo->prepare("SELECT * FROM conversas WHERE id = ?");
$stmt->execute([$conversaId]);
$conversa = $stmt->fetch();

if (!utilizadorPertenceConversa($conversa, $uid)) {
    registarLogSeguranca($pdo, 'acesso_negado_chat', $uid, "Tentativa de envio de mensagem para conversa #$conversaId sem permissão.");
    http_response_code(403);
    echo json_encode(['erro' => 'Sem permissão.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO mensagens_chat (conversa_id, remetente_id, tipo, conteudo) VALUES (?,?,'texto',?)");
$stmt->execute([$conversaId, $uid, $mensagem]);
$novoId = $pdo->lastInsertId();

echo json_encode(['sucesso' => true, 'id' => $novoId, 'criado_em' => date('H:i')]);
