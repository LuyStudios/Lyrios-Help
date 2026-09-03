<?php
$titulo = "Confirmar Email";
require_once __DIR__ . '/../includes/funcoes.php';

$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM verificacoes_email WHERE token = ?");
$stmt->execute([$token]);
$verificacao = $stmt->fetch();

$sucesso = false;
if ($verificacao) {
    $pdo->prepare("UPDATE utilizadores SET email_verificado = 1 WHERE id = ?")->execute([$verificacao['utilizador_id']]);
    $pdo->prepare("DELETE FROM verificacoes_email WHERE utilizador_id = ?")->execute([$verificacao['utilizador_id']]);
    $sucesso = true;
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="form-box">
  <h2>Confirmação de Email</h2>
  <?php if ($sucesso): ?>
    <div class="alert alert-success">Email confirmado com sucesso!</div>
    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-full">Ir para o login</a>
  <?php else: ?>
    <div class="alert alert-error">Este link de confirmação é inválido ou já foi usado.</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
