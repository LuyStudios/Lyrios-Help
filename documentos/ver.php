<?php
require_once __DIR__ . '/../includes/funcoes.php';
if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM certificados WHERE id = ?");
$stmt->execute([$id]);
$cert = $stmt->fetch();

if (!$cert) { http_response_code(404); die('Documento não encontrado.'); }

$autorizado = $_SESSION['tipo'] === 'admin' || $_SESSION['utilizador_id'] == $cert['psicologo_id'];
if (!$autorizado) {
    registarLogSeguranca($pdo, 'acesso_negado_documento', $_SESSION['utilizador_id'], "Tentativa de acesso não autorizado ao certificado #$id");
    http_response_code(403);
    die('Não tens permissão para ver este documento.');
}

$caminhoCompleto = __DIR__ . '/../' . $cert['caminho'];
if (!file_exists($caminhoCompleto)) { http_response_code(404); die('Ficheiro em falta no servidor.'); }

header('Content-Type: ' . $cert['tipo']);
header('Content-Disposition: inline; filename="' . basename($cert['nome_original']) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($caminhoCompleto));
readfile($caminhoCompleto);
exit;
