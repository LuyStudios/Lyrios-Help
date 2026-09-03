<?php
$titulo = "A Nossa História";
require_once __DIR__ . '/includes/funcoes.php';
$depoimentos = $pdo->query("SELECT * FROM depoimentos WHERE ativo = 1 ORDER BY criado_em DESC LIMIT 6")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Desde o início</span>
    <h1>A nossa história</h1>
    <p>Como a Lyrios nasceu para aproximar as pessoas do apoio psicológico que merecem.</p>
  </div>
</section>

<section class="stats-faixa">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item reveal">
        <div class="numero" data-contar="4">0</div>
        <div class="rotulo">Anos de mercado</div>
      </div>
      <div class="stat-item reveal reveal-atraso-1">
        <div class="numero" data-contar="120">0</div>
        <div class="rotulo">Projetos e parcerias desenvolvidos</div>
      </div>
      <div class="stat-item reveal reveal-atraso-2">
        <div class="numero" data-contar="18">0</div>
        <div class="rotulo">Feiras e eventos de saúde mental</div>
      </div>
      <div class="stat-item reveal reveal-atraso-3">
        <div class="numero" data-contar="6">0</div>
        <div class="rotulo">Provincias com presença</div>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="servico-numerado reveal">
      <div class="servico-numerado-texto">
        <span class="kicker">Quem somos</span>
        <h3>Uma plataforma pensada para aproximar pessoas de apoio real</h3>
        <p>A Lyrios nasceu da dificuldade que muitas pessoas sentem em encontrar apoio psicológico próximo de casa. Reunimos uma equipa de psicólogos e tecnologia para resolver esse problema com uma plataforma simples, segura e humana — ligando pacientes a psicólogos certificados através de videochamadas, sem filas de espera nem deslocações.</p>
        <div class="checklist" style="margin-bottom:8px;">
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Confidencialidade em cada conversa</div>
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Empatia e profissionalismo</div>
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Acesso simples, em qualquer lugar</div>
        </div>
      </div>
      <div class="servico-numerado-img">
        <img src="https://images.unsplash.com/photo-1758273241078-8eec353836be?fm=jpg&q=80&w=900&auto=format&fit=crop" alt="Equipa Lyrios">
      </div>
    </div>
  </div>
</section>

<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">O que nos guia</span>
      <h2>Missão, visão e valores</h2>
    </div>
    <div class="grid grid-3">
      <div class="card reveal" style="text-align:center;">
        <div class="icone-gradiente-logo"><i class="fa-solid fa-bullseye"></i></div>
        <h3>Missão</h3>
        <p>Tornar o acompanhamento psicológico acessível, confidencial e de qualidade para todos, em qualquer lugar de Angola e do mundo.</p>
      </div>
      <div class="card reveal reveal-atraso-1" style="text-align:center;">
        <div class="icone-gradiente-logo"><i class="fa-solid fa-eye"></i></div>
        <h3>Visão</h3>
        <p>Ser a plataforma de referência em saúde mental online, reconhecida pela confiança que gera entre pacientes e profissionais.</p>
      </div>
      <div class="card reveal reveal-atraso-2" style="text-align:center;">
        <div class="icone-gradiente-logo"><i class="fa-solid fa-heart"></i></div>
        <h3>Valores</h3>
        <p>Confidencialidade, empatia, profissionalismo e acessibilidade guiam cada funcionalidade que construímos.</p>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($depoimentos)): ?>
<section>
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Vozes reais</span>
      <h2>Relatos de quem já usou a Lyrios</h2>
    </div>
    <div class="grid grid-3">
      <?php foreach ($depoimentos as $i => $d): ?>
      <div class="card reveal reveal-atraso-<?= min($i + 1, 3) ?>">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
          <?php if ($d['foto_url']): ?>
            <img src="<?= escape(urlFoto($d['foto_url'])) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-user"></i></div>
          <?php endif; ?>
          <strong style="color:var(--primary-dark);font-size:14.5px;"><?= escape($d['nome_paciente']) ?></strong>
        </div>
        <p style="font-family:'Fraunces',serif;font-style:italic;font-size:15px;line-height:1.6;color:var(--ink);">"<?= escape($d['texto']) ?>"</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
