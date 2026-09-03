<?php
$titulo = "Contactos";
require_once __DIR__ . '/includes/funcoes.php';

$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($nome === '' || $email === '' || $mensagem === '') {
        $erro = 'Por favor preenche todos os campos obrigatórios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO mensagens_contacto (nome, email, assunto, mensagem) VALUES (?,?,?,?)");
        $stmt->execute([$nome, $email, $assunto, $mensagem]);
        $sucesso = 'Mensagem enviada com sucesso! Entraremos em contacto em breve.';
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Estamos disponíveis</span>
    <h1>Fala connosco</h1>
    <p>Tens dúvidas, sugestões ou precisas de ajuda? Escreve-nos.</p>
  </div>
</section>
<section>
  <div class="container">
    <div class="grid grid-2">
      <div class="form-box reveal" style="margin:0;">
        <h2>Envia uma mensagem</h2>
        <?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
        <form method="post">
          <?php csrfCampo(); ?>
          <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
          <div class="form-group"><label>Assunto</label><input type="text" name="assunto"></div>
          <div class="form-group"><label>Mensagem</label><textarea name="mensagem" required></textarea></div>
          <button class="btn btn-primary btn-full" type="submit">Enviar mensagem</button>
        </form>
      </div>
      <div class="suporte-cartao reveal reveal-atraso-1" style="display:flex;flex-direction:column;justify-content:center;">
        <div class="rotulo">Fala connosco diretamente</div>
        <div class="linha"><i class="fa-solid fa-location-dot"></i> Luanda, Angola</div>
        <div class="linha"><i class="fa-solid fa-envelope"></i> info@lyrios.co.ao</div>
        <div class="linha"><i class="fa-solid fa-phone"></i> +244 900 000 000</div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
