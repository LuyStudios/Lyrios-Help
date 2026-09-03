<?php
/**
 * Espera que a variável $pagina esteja definida (ex: 'dashboard', 'consultas'...)
 * e que $areaTipo esteja definido ('paciente', 'psicologo', 'admin')
 */
$uidAtual = $_SESSION['utilizador_id'] ?? null;
$emailVerificado = true;
if ($uidAtual) {
    $stmt = $pdo->prepare("SELECT email_verificado FROM utilizadores WHERE id = ?");
    $stmt->execute([$uidAtual]);
    $r = $stmt->fetch();
    $emailVerificado = $r ? (bool)$r['email_verificado'] : true;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title><?= isset($titulo) ? escape($titulo) . ' - Lyrios' : 'Lyrios' ?></title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon-32.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script>
  // Aplica o tema guardado antes do CSS pintar, para evitar "flash" de tema errado
  (function(){ var t = localStorage.getItem('lyrios_tema'); if (t === 'escuro') document.documentElement.setAttribute('data-tema','escuro'); })();
</script>
</head>
<body>
<header class="navbar">
  <div class="container">
    <a href="<?= BASE_URL ?>/index.php" class="marca-lyrios">
      <img src="<?= BASE_URL ?>/assets/img/logo-icone.png" alt="Lyrios">
      <span class="texto-marca">Lyrios</span>
    </a>
    <nav>
      <button id="btnTema" class="btn-tema" title="Alternar modo noturno"><i class="fa-solid fa-moon"></i></button>
      <span class="navbar-saudacao">Olá, <?= escape($_SESSION['nome']) ?></span>
      <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-entrar">Sair</a>
    </nav>
  </div>
</header>

<?php if (!$emailVerificado): ?>
<div class="aviso-email-topo">
  <i class="fa-solid fa-triangle-exclamation"></i> Ainda não confirmaste o teu email.
  <a href="<?= BASE_URL ?>/auth/reenviar_verificacao.php">Reenviar confirmação</a>
  <?php if (isset($_GET['email_reenviado'])): ?><span style="margin-left:8px;">✓ Email reenviado</span><?php endif; ?>
</div>
<?php endif; ?>

<div class="dash-wrap">
  <button class="sidebar-toggle-mobile" id="sidebarToggle"><i class="fa-solid fa-bars"></i> Menu</button>
  <aside class="sidebar" id="sidebarLinks">
    <?php if ($areaTipo === 'paciente'): ?>
      <a href="<?= BASE_URL ?>/paciente/dashboard.php" class="<?= $pagina==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Painel</a>
      <a href="<?= BASE_URL ?>/paciente/buscar_psicologos.php" class="<?= $pagina==='buscar'?'active':'' ?>"><i class="fa-solid fa-magnifying-glass"></i> Procurar Psicólogos</a>
      <a href="<?= BASE_URL ?>/paciente/marcar_consulta.php" class="<?= $pagina==='marcar'?'active':'' ?>"><i class="fa-solid fa-calendar-plus"></i> Marcar consulta</a>
      <a href="<?= BASE_URL ?>/paciente/minhas_consultas.php" class="<?= $pagina==='consultas'?'active':'' ?>"><i class="fa-solid fa-calendar-days"></i> Minhas consultas</a>
      <a href="<?= BASE_URL ?>/paciente/mensagens.php" class="<?= $pagina==='mensagens'?'active':'' ?>"><i class="fa-solid fa-comments"></i> Mensagens</a>
      <a href="<?= BASE_URL ?>/paciente/questionario.php" class="<?= $pagina==='questionario'?'active':'' ?>"><i class="fa-solid fa-clipboard-list"></i> Questionário</a>
      <a href="<?= BASE_URL ?>/paciente/atividades.php" class="<?= $pagina==='atividades'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Atividades</a>
      <a href="<?= BASE_URL ?>/paciente/perfil.php" class="<?= $pagina==='perfil'?'active':'' ?>"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <?php elseif ($areaTipo === 'psicologo'): ?>
      <a href="<?= BASE_URL ?>/psicologo/dashboard.php" class="<?= $pagina==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Painel</a>
      <a href="<?= BASE_URL ?>/psicologo/agenda.php" class="<?= $pagina==='agenda'?'active':'' ?>"><i class="fa-solid fa-calendar-days"></i> Agenda de consultas</a>
      <a href="<?= BASE_URL ?>/psicologo/disponibilidade.php" class="<?= $pagina==='disponibilidade'?'active':'' ?>"><i class="fa-solid fa-clock"></i> Disponibilidade</a>
      <a href="<?= BASE_URL ?>/psicologo/relatorio.php" class="<?= $pagina==='relatorio'?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> Relatório e Receita</a>
      <a href="<?= BASE_URL ?>/psicologo/mensagens.php" class="<?= $pagina==='mensagens'?'active':'' ?>"><i class="fa-solid fa-comments"></i> Mensagens</a>
      <a href="<?= BASE_URL ?>/psicologo/atividades.php" class="<?= $pagina==='atividades'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Atividades</a>
      <a href="<?= BASE_URL ?>/psicologo/perfil.php" class="<?= $pagina==='perfil'?'active':'' ?>"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <?php elseif ($areaTipo === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $pagina==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Painel</a>
      <a href="<?= BASE_URL ?>/admin/utilizadores.php" class="<?= $pagina==='utilizadores'?'active':'' ?>"><i class="fa-solid fa-users"></i> Utilizadores</a>
      <a href="<?= BASE_URL ?>/admin/certificados.php" class="<?= $pagina==='documentos'?'active':'' ?>"><i class="fa-solid fa-file-shield"></i> Certificados</a>
      <a href="<?= BASE_URL ?>/admin/consultas.php" class="<?= $pagina==='consultas'?'active':'' ?>"><i class="fa-solid fa-calendar-days"></i> Consultas</a>
      <a href="<?= BASE_URL ?>/admin/pagamentos.php" class="<?= $pagina==='pagamentos'?'active':'' ?>"><i class="fa-solid fa-money-bill-wave"></i> Pagamentos</a>
      <a href="<?= BASE_URL ?>/admin/levantamentos.php" class="<?= $pagina==='levantamentos'?'active':'' ?>"><i class="fa-solid fa-money-bill-transfer"></i> Levantamentos</a>
      <a href="<?= BASE_URL ?>/admin/cupoes.php" class="<?= $pagina==='cupoes'?'active':'' ?>"><i class="fa-solid fa-tags"></i> Cupões / Ofertas</a>
      <a href="<?= BASE_URL ?>/admin/depoimentos.php" class="<?= $pagina==='depoimentos'?'active':'' ?>"><i class="fa-solid fa-quote-left"></i> Depoimentos</a>
      <a href="<?= BASE_URL ?>/admin/questionario.php" class="<?= $pagina==='questionario'?'active':'' ?>"><i class="fa-solid fa-list-check"></i> Questionário</a>
      <a href="<?= BASE_URL ?>/admin/perguntas_registo.php" class="<?= $pagina==='perguntas_registo'?'active':'' ?>"><i class="fa-solid fa-clipboard-question"></i> Perguntas de Registo</a>
      <a href="<?= BASE_URL ?>/admin/servicos.php" class="<?= $pagina==='servicos'?'active':'' ?>"><i class="fa-solid fa-list-check"></i> Serviços</a>
      <a href="<?= BASE_URL ?>/admin/metodos_pagamento.php" class="<?= $pagina==='metodos'?'active':'' ?>"><i class="fa-solid fa-credit-card"></i> Métodos de Pagamento</a>
      <a href="<?= BASE_URL ?>/admin/parceiros.php" class="<?= $pagina==='parceiros'?'active':'' ?>"><i class="fa-solid fa-handshake"></i> Parceiros</a>
      <a href="<?= BASE_URL ?>/admin/mensagens.php" class="<?= $pagina==='mensagens'?'active':'' ?>"><i class="fa-solid fa-envelope"></i> Mensagens</a>
      <a href="<?= BASE_URL ?>/admin/seguranca.php" class="<?= $pagina==='seguranca'?'active':'' ?>"><i class="fa-solid fa-shield-halved"></i> Segurança</a>
      <a href="<?= BASE_URL ?>/admin/configuracoes.php" class="<?= $pagina==='config'?'active':'' ?>"><i class="fa-solid fa-gear"></i> Configurações</a>
    <?php endif; ?>
  </aside>
  <main class="dash-content">
