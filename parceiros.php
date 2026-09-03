<?php
$titulo = "Parceiros";
require_once __DIR__ . '/includes/funcoes.php';
$parceiros = $pdo->query("SELECT * FROM parceiros ORDER BY criado_em DESC")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Trabalhamos em conjunto</span>
    <h1>Os nossos parceiros</h1>
    <p>Instituições e clínicas que colaboram com a Lyrios.</p>
  </div>
</section>
<section>
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($parceiros as $i => $p): ?>
      <div class="card reveal reveal-atraso-<?= min($i + 1, 3) ?>">
        <div class="icon"><i class="fa-solid fa-handshake"></i></div>
        <h3><?= escape($p['nome']) ?></h3>
        <p><?= escape($p['descricao']) ?></p>
      </div>
      <?php endforeach; ?>
      <?php if (empty($parceiros)): ?>
        <p>Ainda não existem parceiros registados.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="cta-final reveal">
      <h2>Queres tornar-te parceiro da Lyrios?</h2>
      <p>Trabalhamos com clínicas, universidades e instituições que partilham o nosso compromisso com a saúde mental.</p>
      <div class="btns">
        <a href="<?= BASE_URL ?>/contactos.php" class="btn btn-primary">Fala connosco</a>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
