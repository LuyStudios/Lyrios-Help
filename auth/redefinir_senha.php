<?php
$titulo = "Redefinir Password";
require_once __DIR__ . '/../includes/funcoes.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$erro = ''; $sucesso = '';

$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND usado = 0 AND expira_em > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $erro = 'Este link de recuperação é inválido ou já expirou. Pede um novo.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $erro = 'A password deve ter pelo menos 8 caracteres, com letras e números.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE utilizadores SET password = ?, tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?")
            ->execute([$hash, $reset['utilizador_id']]);
        $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?")->execute([$reset['id']]);
        registarLogSeguranca($pdo, 'password_redefinida', $reset['utilizador_id'], 'Password redefinida com sucesso via link de recuperação.');
        $sucesso = 'Password redefinida com sucesso! Já podes entrar com a nova password.';
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="form-box">
  <h2>Redefinir Password</h2>
  <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?>
    <div class="alert alert-success"><?= escape($sucesso) ?></div>
    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-full">Ir para o login</a>
  <?php elseif (!$erro): ?>
    <form method="post">
      <?php csrfCampo(); ?>
      <input type="hidden" name="token" value="<?= escape($token) ?>">
      <div class="form-group">
        <label>Nova password</label>
        <input type="password" name="password" required minlength="8">
        <small style="color:#697871;">Mínimo 8 caracteres, com letras e números.</small>
      </div>
      <button class="btn btn-primary btn-full" type="submit">Redefinir password</button>
    </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
