<?php
require_once __DIR__ . '/../includes/funcoes.php';
if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$uid = $_SESSION['utilizador_id'];
$stmt = $pdo->prepare("SELECT nome, email, email_verificado FROM utilizadores WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if ($user && !$user['email_verificado']) {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("DELETE FROM verificacoes_email WHERE utilizador_id = ?")->execute([$uid]);
    $pdo->prepare("INSERT INTO verificacoes_email (utilizador_id, token) VALUES (?,?)")->execute([$uid, $token]);
    $link = 'https://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/verificar_email.php?token=' . $token;
    @mail($user['email'], 'Confirma o teu email - Lyrios', "Olá {$user['nome']},\n\nConfirma o teu email clicando no link:\n$link");
}

$voltar = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
header('Location: ' . $voltar . (strpos($voltar, '?') !== false ? '&' : '?') . 'email_reenviado=1');
exit;
