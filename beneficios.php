<?php
$titulo = "Benefícios";
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Porquê escolher a Lyrios</span>
    <h1>Benefícios do nosso modelo</h1>
    <p>Uma plataforma pensada para pacientes, psicólogos e para quem precisa de encontrar apoio psicológico de confiança.</p>
  </div>
</section>

<section>
  <div class="container">
    <div class="grid grid-2" style="gap:64px;align-items:start;">
      <div class="reveal" style="position:sticky;top:100px;">
        <span class="kicker">Para pacientes</span>
        <h2 style="font-size:clamp(26px,3vw,34px);">O que ganhas ao escolher a Lyrios</h2>
        <p style="color:var(--muted);margin-top:14px;font-size:15.5px;line-height:1.7;">Da primeira consulta ao acompanhamento contínuo, tudo pensado para te dar tranquilidade.</p>
      </div>
      <div class="beneficios-lista reveal reveal-atraso-1">
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-house"></i></div>
          <div><h3>Consultas sem sair de casa</h3><p>Fala com um psicólogo por videochamada, mensagens de texto ou áudio, onde e quando te for mais cómodo.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-shield-check"></i></div>
          <div><h3>Profissionais verificados</h3><p>Todos os psicólogos passam por uma revisão de documentos antes de poderem atender pacientes.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-star"></i></div>
          <div><h3>Avaliações reais</h3><p>Vê a nota média e os comentários de outros pacientes antes de escolheres o teu psicólogo.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-calendar-check"></i></div>
          <div><h3>Flexibilidade total</h3><p>Remarca consultas facilmente e escolhe entre vários métodos de pagamento, incluindo Multicaixa Express.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-lock"></i></div>
          <div><h3>Privacidade protegida</h3><p>As tuas conversas, documentos e dados pessoais são protegidos com as melhores práticas de segurança.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-tags"></i></div>
          <div><h3>Ofertas e descontos</h3><p>Aproveita cupões de desconto em consultas selecionadas, disponibilizados periodicamente.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="grid grid-2" style="gap:64px;align-items:start;">
      <div class="reveal" style="position:sticky;top:100px;">
        <span class="kicker">Para psicólogos</span>
        <h2 style="font-size:clamp(26px,3vw,34px);">Porque trabalhar através da Lyrios</h2>
        <p style="color:var(--muted);margin-top:14px;font-size:15.5px;line-height:1.7;">Foca-te no que fazes melhor — nós tratamos do resto.</p>
      </div>
      <div class="beneficios-lista reveal reveal-atraso-1">
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-users"></i></div>
          <div><h3>Mais pacientes</h3><p>Chega a pacientes que procuram apoio psicológico online, sem custos de marketing próprio.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-calendar-days"></i></div>
          <div><h3>Agenda flexível</h3><p>Define os teus próprios dias e horários disponíveis, e controla o limite diário de consultas.</p></div>
        </div>
        <div class="beneficio-linha">
          <div class="icone"><i class="fa-solid fa-money-bill-wave"></i></div>
          <div><h3>Pagamento simples</h3><p>Recebe automaticamente a tua parte de cada consulta, com relatórios claros e levantamentos fáceis.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section style="background:#fff;border-top:1px solid var(--line);">
  <div class="container">
    <div class="section-title reveal">
      <span class="kicker" style="justify-content:center;">Resumo</span>
      <h2>Tudo o que precisas, num só sítio</h2>
    </div>
    <div class="checklist" style="max-width:520px;margin:0 auto;">
      <div class="checklist-item"><i class="fa-solid fa-check"></i> Psicólogos verificados e avaliados por pacientes reais</div>
      <div class="checklist-item"><i class="fa-solid fa-check"></i> Videochamada, texto e áudio, sem sair da plataforma</div>
      <div class="checklist-item"><i class="fa-solid fa-check"></i> Pagamentos seguros com Multicaixa Express</div>
      <div class="checklist-item"><i class="fa-solid fa-check"></i> Dados e conversas protegidos de ponta a ponta</div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="cta-final reveal">
      <h2>Pronto para começar?</h2>
      <p>Cria a tua conta em minutos e junta-te à Lyrios hoje.</p>
      <div class="btns">
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="btn btn-primary">Criar conta de paciente</a>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=psicologo" class="btn btn-outline">Sou psicólogo</a>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
