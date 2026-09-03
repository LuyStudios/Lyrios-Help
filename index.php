<?php
$titulo = "Início";
require_once __DIR__ . '/includes/funcoes.php';

$servicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 LIMIT 4")->fetchAll();

$totalPsicologos = $pdo->query("SELECT COUNT(*) c FROM utilizadores u JOIN perfis_psicologos p ON p.utilizador_id=u.id WHERE u.tipo='psicologo' AND u.estado='ativo' AND p.aprovado=1")->fetch()['c'];
$totalConsultas = $pdo->query("SELECT COUNT(*) c FROM consultas WHERE estado='concluida'")->fetch()['c'];
$totalPacientes = $pdo->query("SELECT COUNT(*) c FROM utilizadores WHERE tipo='paciente'")->fetch()['c'];
$mediaGeral = $pdo->query("SELECT COALESCE(AVG(nota),0) m FROM avaliacoes")->fetch()['m'];

$psicologosDestaque = $pdo->query("
    SELECT u.id, u.nome, u.foto, p.especialidade, p.preco_sessao,
        (SELECT COUNT(*) FROM certificados c WHERE c.psicologo_id = u.id AND c.estado='aprovado') AS verificado
    FROM utilizadores u JOIN perfis_psicologos p ON p.utilizador_id = u.id
    WHERE u.tipo='psicologo' AND u.estado='ativo' AND p.aprovado = 1
    ORDER BY (SELECT COALESCE(AVG(nota),0) FROM avaliacoes a JOIN consultas c ON c.id=a.consulta_id WHERE c.psicologo_id = u.id) DESC
    LIMIT 3
")->fetchAll();
foreach ($psicologosDestaque as &$p) { $p['avaliacao'] = mediaAvaliacoes($pdo, $p['id']); }
unset($p);

$depoimentos = $pdo->query("SELECT * FROM depoimentos WHERE ativo = 1 ORDER BY criado_em DESC LIMIT 3")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="hero hero-com-imagem">
  <div class="container">
    <div class="hero-texto">
      <span class="kicker claro">Consultas online de psicologia</span>
      <h1>Cuida da tua saúde mental sem sair de casa</h1>
      <p>A Lyrios liga-te a psicólogos verificados para consultas por videochamada, texto ou áudio — de forma privada, ao teu ritmo.</p>
      <div class="btns">
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Encontrar o meu psicólogo</a>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=psicologo" class="btn btn-outline">Sou psicólogo</a>
      </div>
      <?php if (!empty($depoimentos)): ?>
      <div class="confianca-social">
        <div class="avatares-empilhados">
          <?php foreach (array_slice($depoimentos, 0, 3) as $d): ?>
            <?php if ($d['foto_url']): ?><img src="<?= escape(urlFoto($d['foto_url'])) ?>" alt=""><?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div class="texto"><strong><?= number_format($totalPacientes) ?>+ pessoas</strong> já confiam na Lyrios</div>
      </div>
      <?php endif; ?>
    </div>
    <div class="hero-visual reveal">
      <div class="hero-imagem-moldura">
        <img src="https://images.unsplash.com/photo-1752650733337-cb0189176fb9?fm=jpg&q=80&w=800&auto=format&fit=crop" alt="Pessoa numa consulta online pela Lyrios">
      </div>
      <div class="hero-credencial">
        <div class="icone"><i class="fa-solid fa-shield-check"></i></div>
        <div><strong>Psicólogos verificados</strong><span>Documentos revistos por um administrador</span></div>
      </div>
    </div>
  </div>
</section>

<section class="stats-faixa">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item reveal">
        <div class="numero" data-contar="<?= $totalPsicologos ?>">0</div>
        <div class="rotulo">Psicólogos verificados</div>
      </div>
      <div class="stat-item reveal reveal-atraso-1">
        <div class="numero" data-contar="<?= $totalConsultas ?>">0</div>
        <div class="rotulo">Consultas realizadas</div>
      </div>
      <div class="stat-item reveal reveal-atraso-2">
        <div class="numero" data-contar="<?= $totalPacientes ?>">0</div>
        <div class="rotulo">Pacientes acolhidos</div>
      </div>
      <div class="stat-item reveal reveal-atraso-3">
        <div class="numero" data-contar="<?= round($mediaGeral, 1) ?>" data-decimais="1">0</div>
        <div class="rotulo">Avaliação média</div>
      </div>
    </div>
  </div>
</section>

<section style="padding:56px 0;background:#fff;border-bottom:1px solid var(--line);">
  <div class="container">
    <p class="reveal" style="text-align:center;color:var(--muted);font-size:12.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:28px;">Confiado por equipas e instituições de vários setores</p>
    <div class="confianca-parceiros reveal">
      <span>NEXUS SAÚDE</span>
      <span>VÍTAE GROUP</span>
      <span>AURORA TECH</span>
      <span>MERIDIAN CAPITAL</span>
      <span>PRISMA SEGUROS</span>
      <span>ATLAS EDUCAÇÃO</span>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Como funciona</span>
      <h2>Três passos até à tua primeira sessão</h2>
    </div>
    <div class="processo">
      <div class="processo-passo reveal">
        <div class="processo-numero">01</div>
        <h3>Cria a tua conta</h3>
        <p>Um pequeno questionário ajuda-nos a perceber o que procuras, em poucos minutos.</p>
      </div>
      <div class="processo-passo reveal reveal-atraso-1">
        <div class="processo-numero">02</div>
        <h3>Escolhe o teu psicólogo</h3>
        <p>Filtra por especialidade e compara avaliações reais de outros pacientes.</p>
      </div>
      <div class="processo-passo reveal reveal-atraso-2">
        <div class="processo-numero">03</div>
        <h3>Fala com ele online</h3>
        <p>Videochamada, texto ou áudio — tudo dentro da plataforma, quando precisares.</p>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($psicologosDestaque)): ?>
<section style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Melhor avaliados</span>
      <h2>Psicólogos em destaque</h2>
    </div>
    <div class="grid grid-3">
      <?php foreach ($psicologosDestaque as $i => $p): ?>
      <div class="destaque-psicologo reveal reveal-atraso-<?= min($i + 1, 3) ?>">
        <?php if ($p['foto']): ?>
          <img src="<?= BASE_URL ?>/<?= escape($p['foto']) ?>" style="width:72px;height:72px;border-radius:50%;object-fit:cover;margin:0 auto 16px;border:2px solid var(--primary-light);">
        <?php else: ?>
          <div style="width:72px;height:72px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;"><i class="fa-solid fa-user"></i></div>
        <?php endif; ?>
        <h3 style="margin:0 0 4px;font-size:16.5px;font-weight:500;"><?= escape($p['nome']) ?></h3>
        <div style="color:var(--muted);font-size:13px;margin-bottom:10px;"><?= escape($p['especialidade'] ?: 'Psicólogo(a)') ?></div>
        <?php if ($p['verificado'] > 0): ?><span class="selo-verificado"><i class="fa-solid fa-shield-check"></i> Verificado</span><?php endif; ?>
        <div style="margin-top:12px;"><?= estrelasHtml($p['avaliacao']['media'], $p['avaliacao']['total']) ?></div>
        <p style="font-weight:600;color:var(--primary-dark);margin:12px 0 0;font-family:'Fraunces',serif;"><?= formatarKz($p['preco_sessao']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:40px;">
      <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Ver todos os psicólogos</a>
    </div>
  </div>
</section>
<?php endif; ?>

<section>
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">O que oferecemos</span>
      <h2>Soluções de apoio psicológico em que podes confiar</h2>
      <p>Da consulta individual ao acompanhamento de casal, cada serviço é conduzido por profissionais verificados, com o mesmo rigor e cuidado.</p>
    </div>

    <div class="servico-numerado reveal">
      <div class="servico-numerado-texto">
        <div class="servico-numerado-numero">01</div>
        <h3>Consulta Individual</h3>
        <p>Sessões dedicadas a ansiedade, autoestima, stress e desenvolvimento pessoal, num espaço confidencial e sem julgamentos, ao teu ritmo.</p>
        <a href="<?= BASE_URL ?>/servicos.php" class="btn btn-outline" style="color:var(--primary);border-color:var(--primary);">Ver mais</a>
      </div>
      <div class="servico-numerado-img">
        <img src="https://images.unsplash.com/photo-1758273241086-f3585ef8c2f8?fm=jpg&q=80&w=900&auto=format&fit=crop" alt="Consulta individual de psicologia">
      </div>
    </div>

    <div class="servico-numerado inverso reveal">
      <div class="servico-numerado-texto">
        <div class="servico-numerado-numero">02</div>
        <h3>Terapia de Casal</h3>
        <p>Apoio à comunicação e à relação, com um psicólogo especializado a acompanhar os dois lados com equilíbrio e imparcialidade.</p>
        <a href="<?= BASE_URL ?>/servicos.php" class="btn btn-outline" style="color:var(--primary);border-color:var(--primary);">Ver mais</a>
      </div>
      <div class="servico-numerado-img">
        <img src="https://images.unsplash.com/photo-1758273241078-8eec353836be?fm=jpg&q=80&w=900&auto=format&fit=crop" alt="Terapia de casal">
      </div>
    </div>

    <div class="servico-numerado reveal">
      <div class="servico-numerado-texto">
        <div class="servico-numerado-numero">03</div>
        <h3>Acompanhamento Contínuo</h3>
        <p>Fica em contacto com o teu psicólogo entre sessões, por mensagem de texto ou áudio, sempre que precisares de apoio.</p>
        <a href="<?= BASE_URL ?>/servicos.php" class="btn btn-outline" style="color:var(--primary);border-color:var(--primary);">Ver mais</a>
      </div>
      <div class="servico-numerado-img">
        <img src="https://images.unsplash.com/photo-1752650733337-cb0189176fb9?fm=jpg&q=80&w=900&auto=format&fit=crop" alt="Acompanhamento online contínuo">
      </div>
    </div>

    <div style="text-align:center;margin-top:20px;">
      <a href="<?= BASE_URL ?>/servicos.php" class="btn btn-primary">Ver todos os serviços</a>
    </div>
  </div>
</section>

<section class="stats-imagem" style="background-image:url('https://images.unsplash.com/photo-1752650733337-cb0189176fb9?fm=jpg&q=60&w=1600&auto=format&fit=crop');">
  <div class="container">
    <div class="intro reveal">
      <span class="kicker claro">Resultados</span>
      <h2>Confiança construída sessão após sessão</h2>
      <p>O nosso desempenho reflete-se em números concretos, alcançados com rigor, verificação cuidadosa de cada profissional e acompanhamento contínuo da experiência de cada paciente.</p>
    </div>
    <div class="stats-imagem-grid">
      <div class="stats-imagem-item reveal">
        <div class="numero" data-contar="<?= $totalConsultas ?>">0</div>
        <div class="rotulo">Consultas realizadas</div>
        <p>Sessões concluídas com sucesso na plataforma.</p>
      </div>
      <div class="stats-imagem-item reveal reveal-atraso-1">
        <div class="numero" data-contar="<?= $totalPsicologos ?>">0</div>
        <div class="rotulo">Psicólogos verificados</div>
        <p>Cada um com documentos revistos por um administrador.</p>
      </div>
      <div class="stats-imagem-item reveal reveal-atraso-2">
        <div class="numero" data-contar="<?= $totalPacientes ?>">0</div>
        <div class="rotulo">Pacientes acolhidos</div>
        <p>Pessoas que já deram o primeiro passo.</p>
      </div>
      <div class="stats-imagem-item reveal reveal-atraso-3">
        <div class="numero" data-contar="<?= round($mediaGeral, 1) ?>" data-decimais="1">0</div>
        <div class="rotulo">Avaliação média</div>
        <p>Com base em avaliações reais de pacientes.</p>
      </div>
    </div>
  </div>
</section>

<section style="background:#fff;">
  <div class="container">
    <div class="grid grid-2" style="gap:64px;align-items:center;">
      <div class="reveal">
        <span class="kicker">Porquê a Lyrios</span>
        <h2 style="font-size:clamp(26px,3vw,34px);margin-bottom:14px;">Compromisso com a confiança em cada consulta</h2>
        <p style="color:var(--muted);font-size:15.5px;line-height:1.7;">Cada detalhe da plataforma foi pensado para que te sintas seguro a dar o primeiro passo — da verificação de profissionais à privacidade de cada conversa.</p>
        <div class="checklist">
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Profissionais com documentos verificados</div>
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Conversas e chamadas protegidas</div>
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Avaliações reais de outros pacientes</div>
          <div class="checklist-item"><i class="fa-solid fa-check"></i> Cancelamento e remarcação flexíveis</div>
        </div>
        <a href="<?= BASE_URL ?>/beneficios.php" class="btn btn-primary">Ver todos os benefícios</a>
      </div>
      <div class="beneficios-lista reveal reveal-atraso-1">
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-shield-check"></i></div>
          <div><h3>Profissionais verificados</h3><p>Cada psicólogo envia documentos de qualificação, revistos por um administrador antes de atender.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-lock"></i></div>
          <div><h3>Privacidade em primeiro lugar</h3><p>Conversas, documentos e chamadas protegidos, com acesso restrito apenas a quem participa.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-star"></i></div>
          <div><h3>Transparência total</h3><p>Vês sempre a avaliação real de outros pacientes antes de escolheres o teu psicólogo.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($depoimentos)): ?>
<section style="border-top:1px solid var(--line);">
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Vozes reais</span>
      <h2>Quem já usou, recomenda</h2>
    </div>
    <div class="depoimentos-palco reveal" id="palcoDepoimentos">
      <?php foreach ($depoimentos as $i => $d): ?>
      <div class="depoimento-slide <?= $i === 0 ? 'ativo' : '' ?>">
        <blockquote>"<?= escape($d['texto']) ?>"</blockquote>
        <div class="autor">
          <?php if ($d['foto_url']): ?>
            <img src="<?= escape(urlFoto($d['foto_url'])) ?>" alt="<?= escape($d['nome_paciente']) ?>">
          <?php else: ?>
            <div class="placeholder-avatar"><i class="fa-solid fa-user"></i></div>
          <?php endif; ?>
          <strong><?= escape($d['nome_paciente']) ?></strong>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($depoimentos) > 1): ?>
    <div class="depoimentos-tabs" id="tabsDepoimentos">
      <?php foreach ($depoimentos as $i => $d): ?>
        <button type="button" class="depoimento-tab <?= $i === 0 ? 'ativo' : '' ?>" data-indice="<?= $i ?>" aria-label="Depoimento <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Dúvidas rápidas</span>
      <h2>Perguntas frequentes</h2>
    </div>
    <div class="faq-com-imagem reveal">
      <div>
        <div class="faq-imagem-lateral">
          <img src="https://images.unsplash.com/photo-1758273241086-f3585ef8c2f8?fm=jpg&q=80&w=700&auto=format&fit=crop" alt="Apoio psicológico Lyrios">
        </div>
        <div class="suporte-cartao">
          <div class="rotulo">Precisas de ajuda?</div>
          <div class="linha"><i class="fa-solid fa-phone"></i> +244 900 000 000</div>
          <div class="linha"><i class="fa-solid fa-envelope"></i> info@lyrios.co.ao</div>
        </div>
      </div>
      <div>
        <div class="faq-teaser-item"><strong>Como funcionam as sessões?</strong><span>Por videochamada, mensagens de texto ou áudio, diretamente na plataforma.</span></div>
        <div class="faq-teaser-item"><strong>Os psicólogos são verificados?</strong><span>Sim, todos passam por revisão de documentos antes de serem aprovados.</span></div>
        <div class="faq-teaser-item"><strong>Posso remarcar uma consulta?</strong><span>Sim, facilmente, na área "Minhas Consultas".</span></div>
        <div class="faq-teaser-item"><strong>Que métodos de pagamento existem?</strong><span>Multicaixa Express e outros métodos ativados pela plataforma.</span></div>
        <div style="margin-top:24px;">
          <a href="<?= BASE_URL ?>/perguntas_frequentes.php" style="color:var(--primary);font-weight:600;font-size:14.5px;">Ver todas as perguntas &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="cta-final reveal">
      <h2>O primeiro passo é o mais importante</h2>
      <p>Cria a tua conta gratuita hoje e encontra o psicólogo certo para ti.</p>
      <div class="btns">
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Criar conta gratuita</a>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=psicologo" class="btn btn-outline">Trabalhar na Lyrios</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
