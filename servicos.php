<?php
$titulo = "Serviços";
require_once __DIR__ . '/includes/funcoes.php';
$servicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY id ASC")->fetchAll();

$imagensServico = [
    'https://images.unsplash.com/photo-1758273241086-f3585ef8c2f8?fm=jpg&q=80&w=900&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1758273241078-8eec353836be?fm=jpg&q=80&w=900&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1752650733337-cb0189176fb9?fm=jpg&q=80&w=900&auto=format&fit=crop',
];

require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Catálogo completo</span>
    <h1>Os nossos serviços</h1>
    <p>Diferentes tipos de apoio psicológico, cada um conduzido por profissionais verificados, adaptados às tuas necessidades.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if (empty($servicos)): ?>
      <p style="text-align:center;color:var(--muted);">Ainda não existem serviços disponíveis.</p>
    <?php endif; ?>
    <?php foreach ($servicos as $i => $s): ?>
    <div class="servico-numerado <?= $i % 2 === 1 ? 'inverso' : '' ?> reveal">
      <div class="servico-numerado-texto">
        <div class="servico-numerado-numero"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
        <h3><?= escape($s['nome']) ?></h3>
        <p><?= escape($s['descricao']) ?></p>
        <p style="font-family:'Fraunces',serif;font-size:19px;color:var(--primary-dark);margin-bottom:22px;">
          <?= formatarKz($s['preco_base']) ?> <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:var(--muted);font-weight:500;">/ sessão</span>
        </p>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Marcar consulta</a>
      </div>
      <div class="servico-numerado-img">
        <img src="<?= escape($imagensServico[$i % count($imagensServico)]) ?>" alt="<?= escape($s['nome']) ?>">
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="cta-final reveal">
      <h2>Não sabes qual escolher?</h2>
      <p>Cria a tua conta e responde a um breve questionário — ajudamos-te a encontrar o serviço certo.</p>
      <div class="btns">
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Começar agora</a>
        <a href="<?= BASE_URL ?>/perguntas_frequentes.php" class="btn btn-outline">Ver perguntas frequentes</a>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
