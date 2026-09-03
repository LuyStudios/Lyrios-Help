<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/util.php';

if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT m.*, c.paciente_id, c.psicologo_id FROM mensagens_chat m JOIN conversas c ON c.id = m.conversa_id WHERE m.id = ? AND m.tipo = 'audio'");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) { http_response_code(404); die('Áudio não encontrado.'); }

$uid = $_SESSION['utilizador_id'];
if ((int)$msg['paciente_id'] !== (int)$uid && (int)$msg['psicologo_id'] !== (int)$uid) {
    registarLogSeguranca($pdo, 'acesso_negado_audio', $uid, "Tentativa de acesso não autorizado ao áudio #$id");
    http_response_code(403);
    die('Sem permissão.');
}

$caminho = __DIR__ . '/../' . $msg['ficheiro_audio'];
if (!file_exists($caminho)) { http_response_code(404); die('Ficheiro em falta.'); }

$extensoes = ['webm' => 'audio/webm', 'ogg' => 'audio/ogg', 'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav', 'aac' => 'audio/aac'];
$ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$contentType = $extensoes[$ext] ?? 'application/octet-stream';

$tamanho = filesize($caminho);
$inicio = 0;
$fim = $tamanho - 1;

header('Content-Type: ' . $contentType);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
header('Accept-Ranges: bytes');

// O Safari (iPhone/iPad/Mac) exige suporte real a pedidos parciais (Range) para
// reproduzir áudio corretamente — sem isto, o áudio pode não tocar de todo nesses
// dispositivos, mesmo que toque normalmente noutros browsers.
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    $inicio = $m[1] === '' ? 0 : (int)$m[1];
    $fim = $m[2] === '' ? $tamanho - 1 : (int)$m[2];
    $fim = min($fim, $tamanho - 1);
    http_response_code(206);
    header("Content-Range: bytes $inicio-$fim/$tamanho");
}

$comprimento = $fim - $inicio + 1;
header('Content-Length: ' . $comprimento);

$fp = fopen($caminho, 'rb');
fseek($fp, $inicio);
$restante = $comprimento;
while ($restante > 0 && !feof($fp)) {
    $ler = min(8192, $restante);
    echo fread($fp, $ler);
    $restante -= $ler;
    flush();
}
fclose($fp);
exit;
