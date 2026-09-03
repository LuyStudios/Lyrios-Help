<?php
$titulo = "Recuperar Password";
require_once __DIR__ . '/../includes/funcoes.php';
if (estaLogado()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$sucesso = ''; $erro = ''; $linkTeste = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $erro = 'Indica um email válido.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nome FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Por segurança, mostramos sempre a mesma mensagem, exista ou não a conta
        $sucesso = 'Se existir uma conta com este email, foi enviado um link de recuperação.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 3600); // 1 hora
            $pdo->prepare("INSERT INTO password_resets (utilizador_id, token, expira_em) VALUES (?,?,?)")
                ->execute([$user['id'], $token, $expira]);

            $link = 'https://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/redefinir_senha.php?token=' . $token;
            $corpo = "Olá {$user['nome']},\n\nRecebemos um pedido para redefinir a tua password na Lyrios.\nClica no link abaixo (válido por 1 hora):\n$link\n\nSe não foste tu, ignora este email.";
            @mail($email, 'Recuperação de password - Lyrios', $corpo);

            registarLogSeguranca($pdo, 'pedido_recuperacao_password', $user['id'], 'Pedido de recuperação de password.');

            // Ambiente de testes/local: como pode não haver servidor de email configurado,
            // mostramos aqui o link diretamente para facilitar os testes.
            $linkTeste = $link;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="form-box">
  <h2>Recuperar Password</h2>
  <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?>
    <div class="alert alert-success"><?= escape($sucesso) ?></div>
    <?php if ($linkTeste): ?>
      <div class="alert alert-error">
        <strong>Ambiente de testes:</strong> como o servidor pode não ter email configurado, aqui está o link diretamente:<br>
        <a href="<?= escape($linkTeste) ?>"><?= escape($linkTeste) ?></a>
      </div>
    <?php endif; ?>
  <?php else: ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>O teu email</label><input type="email" name="email" required></div>
    <button class="btn btn-primary btn-full" type="submit">Enviar link de recuperação</button>
  </form>
  <?php endif; ?>
  <p style="text-align:center;margin-top:16px;font-size:14px;"><a href="<?= BASE_URL ?>/auth/login.php" style="color:#1f6f5c;font-weight:600;">Voltar ao login</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
