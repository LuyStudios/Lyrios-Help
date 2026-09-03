<?php
$titulo = "Perguntas Frequentes";
require_once __DIR__ . '/includes/header.php';

$perguntas = [
    ['Como marco a minha primeira consulta?', 'Cria uma conta gratuita de paciente, procura um psicólogo na página "Procurar Psicólogos", escolhe o horário e efetua o pagamento. A consulta fica confirmada assim que o pagamento for validado.'],
    ['Como funcionam as sessões?', 'As sessões são feitas por videochamada diretamente na plataforma. Também podes trocar mensagens de texto e áudio com o teu psicólogo entre sessões.'],
    ['Os psicólogos são verificados?', 'Sim. Todos os psicólogos enviam documentos de qualificação profissional que são revistos por um administrador antes da conta ser aprovada. Procura o selo "Verificado".'],
    ['Posso remarcar ou cancelar uma consulta?', 'Podes remarcar consultas pendentes ou confirmadas na página "Minhas Consultas", respeitando os limites de disponibilidade do psicólogo.'],
    ['Que métodos de pagamento existem?', 'A plataforma suporta Multicaixa Express e outros métodos que o administrador ativar. O valor é dividido automaticamente entre o psicólogo e a plataforma.'],
    ['Os meus dados estão seguros?', 'Sim. Usamos ligação encriptada, proteção contra acessos indevidos, e os documentos/certificados só são visíveis para ti e para o administrador.'],
    ['Tenho de ter uma idade mínima para criar conta?', 'Sim, é necessário ter pelo menos 16 anos para criar uma conta na Lyrios.'],
];
?>
<section class="hero">
  <div class="container">
    <span class="kicker claro">Tira as tuas dúvidas</span>
    <h1>Perguntas Frequentes</h1>
    <p>Reunimos as respostas às dúvidas mais comuns sobre a Lyrios.</p>
  </div>
</section>
<section>
  <div class="container reveal" style="max-width:800px;">
    <?php foreach ($perguntas as $p): ?>
    <details class="faq-item">
      <summary><?= escape($p[0]) ?></summary>
      <p><?= escape($p[1]) ?></p>
    </details>
    <?php endforeach; ?>
    <div class="suporte-cartao" style="max-width:420px;margin:36px auto 0;">
      <div class="rotulo">Não encontraste a tua resposta?</div>
      <div class="linha"><i class="fa-solid fa-envelope"></i> info@lyrios.co.ao</div>
      <div class="linha"><i class="fa-solid fa-phone"></i> +244 900 000 000</div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
