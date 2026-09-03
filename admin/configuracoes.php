<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('admin');
$titulo = "Configurações"; $pagina = 'config'; $areaTipo = 'admin';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $percentagem = (float)$_POST['percentagem_comissao'];
    $nome = trim($_POST['nome_plataforma']);
    $pdo->prepare("UPDATE configuracoes SET percentagem_comissao=?, nome_plataforma=? WHERE id=1")->execute([$percentagem, $nome]);
    $sucesso = 'Configurações atualizadas com sucesso.';
}

$config = $pdo->query("SELECT * FROM configuracoes LIMIT 1")->fetch();
require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Configurações da Plataforma</h1>
<div class="card" style="max-width:500px;">
  <?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group">
      <label>Percentagem de comissão da plataforma (%)</label>
      <input type="number" step="0.01" name="percentagem_comissao" value="<?= escape($config['percentagem_comissao']) ?>" required>
      <small style="color:#6b8078;">Aplicada automaticamente a cada pagamento de consulta.</small>
    </div>
    <div class="form-group"><label>Nome da plataforma</label><input type="text" name="nome_plataforma" value="<?= escape($config['nome_plataforma']) ?>"></div>
    <button class="btn btn-primary btn-full" type="submit">Guardar configurações</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
