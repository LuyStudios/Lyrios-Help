<?php
$titulo = "Entrar";
require_once __DIR__ . '/../includes/funcoes.php';

if (estaLogado()) { header('Location: ' . BASE_URL . '/index.php'); exit; }
$erro = '';
$aviso = isset($_GET['registado']) ? 'Conta criada com sucesso! Aguarda a aprovação do administrador para poderes entrar.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $minutosBloqueio = verificarBloqueioLogin($pdo, $email);

    if ($minutosBloqueio > 0) {
        $erro = "Demasiadas tentativas falhadas. Tenta novamente dentro de $minutosBloqueio minuto(s).";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            if ($user) registarTentativaFalhada($pdo, $email);
            registarLogSeguranca($pdo, 'login_falhado', $user['id'] ?? null, "Tentativa de login falhada para o email: $email");
            $erro = 'Email ou password incorretos.';
        } elseif ($user['estado'] === 'bloqueado') {
            $erro = 'A tua conta está bloqueada. Contacta o suporte.';
        } elseif ($user['estado'] === 'pendente') {
            $erro = 'A tua conta de psicólogo ainda aguarda aprovação do administrador.';
        } else {
            limparTentativasLogin($pdo, $user['id']);
            session_regenerate_id(true); // previne fixação de sessão

            $_SESSION['utilizador_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['tipo'] = $user['tipo'];
            registarAtividade($pdo, $user['id'], 'Iniciou sessão na plataforma.');
            registarLogSeguranca($pdo, 'login_sucesso', $user['id'], 'Login efetuado com sucesso.');

            if ($user['tipo'] === 'admin') header('Location: ' . BASE_URL . '/admin/dashboard.php');
            elseif ($user['tipo'] === 'psicologo') header('Location: ' . BASE_URL . '/psicologo/dashboard.php');
            else header('Location: ' . BASE_URL . '/paciente/dashboard.php');
            exit;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="form-box">
  <h2>Entrar na tua conta</h2>
  <?php if ($aviso): ?><div class="alert alert-success"><?= escape($aviso) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-primary btn-full" type="submit">Entrar</button>
  </form>
  <p style="text-align:center;margin-top:16px;font-size:14px;">Ainda não tens conta? <a href="<?= BASE_URL ?>/auth/registar.php" style="color:#1f6f5c;font-weight:600;">Regista-te</a></p>
  <p style="text-align:center;margin-top:6px;font-size:13.5px;"><a href="<?= BASE_URL ?>/auth/recuperar_senha.php" style="color:#697871;">Esqueceste-te da password?</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
