<?php require_once __DIR__ . '/funcoes.php'; ?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title><?= isset($titulo) ? escape($titulo) . ' - Lyrios' : 'Lyrios - Consultas de Psicologia Online' ?></title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon-32.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script>
  (function(){ var t = localStorage.getItem('lyrios_tema'); if (t === 'escuro') document.documentElement.setAttribute('data-tema','escuro'); })();
</script>
</head>
<body>
<div class="topbar">
  <div class="container">
    <div class="contactos-topo">
      <span><i class="fa-solid fa-envelope"></i> info@lyrios.co.ao</span>
      <span><i class="fa-solid fa-location-dot"></i> Luanda, Angola</span>
    </div>
    <div class="redes-topo">
      <a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
    </div>
  </div>
</div>
<header class="navbar">
  <div class="container">
    <a href="<?= BASE_URL ?>/index.php" class="marca-lyrios">
      <img src="<?= BASE_URL ?>/assets/img/logo-icone.png" alt="Lyrios">
      <span class="texto-marca">Lyrios</span>
    </a>
    <button class="nav-toggle-mobile" id="navToggle" aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></button>
    <nav id="navLinks">
      <a href="<?= BASE_URL ?>/index.php">Início</a>
      <a href="<?= BASE_URL ?>/historia.php">História</a>
      <a href="<?= BASE_URL ?>/servicos.php">Serviços</a>
      <a href="<?= BASE_URL ?>/beneficios.php">Benefícios</a>
      <a href="<?= BASE_URL ?>/apoios.php">Apoios</a>
      <a href="<?= BASE_URL ?>/parceiros.php">Parceiros</a>
      <a href="<?= BASE_URL ?>/perguntas_frequentes.php">FAQ</a>
      <a href="<?= BASE_URL ?>/contactos.php">Contactos</a>
      <button id="btnTema" class="btn-tema" title="Alternar modo noturno"><i class="fa-solid fa-moon"></i></button>
      <?php if (estaLogado()): ?>
        <?php $tipo = $_SESSION['tipo']; ?>
        <a href="<?= BASE_URL ?>/<?= $tipo === 'admin' ? 'admin' : ($tipo === 'psicologo' ? 'psicologo' : 'paciente') ?>/dashboard.php" class="btn-entrar">Minha Área</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-entrar">Entrar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if (!estaLogado()): ?>
<a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente" class="cta-flutuante" id="ctaFlutuante">
  <span class="bola-icone"><i class="fa-solid fa-arrow-right"></i></span>
  <span class="texto-longa">Criar conta grátis</span>
</a>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/efeitos-premium.js"></script>
<script>
  (function(){
    var toggle = document.getElementById('navToggle');
    var links = document.getElementById('navLinks');
    if (toggle && links) toggle.addEventListener('click', function(){ links.classList.toggle('aberta'); });
    var btnTema = document.getElementById('btnTema');
    if (btnTema) {
      function atualizarIcone(){
        var escuro = document.documentElement.getAttribute('data-tema') === 'escuro';
        btnTema.innerHTML = escuro ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
      }
      atualizarIcone();
      btnTema.addEventListener('click', function(){
        var escuro = document.documentElement.getAttribute('data-tema') === 'escuro';
        if (escuro) { document.documentElement.removeAttribute('data-tema'); localStorage.setItem('lyrios_tema','claro'); }
        else { document.documentElement.setAttribute('data-tema','escuro'); localStorage.setItem('lyrios_tema','escuro'); }
        atualizarIcone();
      });
    }
  })();
</script>
<script>
  // Animações de scroll (reveal) e contadores animados — usadas em várias páginas públicas
  document.addEventListener('DOMContentLoaded', function(){
    var observador = new IntersectionObserver(function(entradas){
      entradas.forEach(function(e){
        if (e.isIntersecting) {
          e.target.classList.add('visivel');
          if (e.target.hasAttribute('data-contar')) animarContador(e.target);
          var contadorFilho = e.target.querySelector('[data-contar]');
          if (contadorFilho) animarContador(contadorFilho);
          observador.unobserve(e.target);
        }
      });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal').forEach(function(el){ observador.observe(el); });

    function animarContador(el){
      var alvo = parseFloat(el.getAttribute('data-contar'));
      var casasDecimais = el.getAttribute('data-decimais') ? parseInt(el.getAttribute('data-decimais')) : 0;
      var duracao = 1400, inicio = null;
      function passo(timestamp){
        if (!inicio) inicio = timestamp;
        var progresso = Math.min((timestamp - inicio) / duracao, 1);
        var valor = alvo * progresso;
        el.textContent = valor.toFixed(casasDecimais) + (el.getAttribute('data-sufixo') || '');
        if (progresso < 1) requestAnimationFrame(passo);
      }
      requestAnimationFrame(passo);
    }

    // Depoimentos — transição suave entre citações (crossfade automático)
    var palco = document.getElementById('palcoDepoimentos');
    if (palco) {
      var slides = palco.querySelectorAll('.depoimento-slide');
      var tabs = document.querySelectorAll('#tabsDepoimentos .depoimento-tab');
      var indiceAtual = 0;
      var temporizador = null;

      function irParaDepoimento(indice) {
        indiceAtual = (indice + slides.length) % slides.length;
        slides.forEach(function(s, i){ s.classList.toggle('ativo', i === indiceAtual); });
        tabs.forEach(function(t, i){ t.classList.toggle('ativo', i === indiceAtual); });
      }

      function reiniciarAutoplayDepoimentos() {
        if (temporizador) clearInterval(temporizador);
        temporizador = setInterval(function(){ irParaDepoimento(indiceAtual + 1); }, 6000);
      }

      tabs.forEach(function(t){
        t.addEventListener('click', function(){
          irParaDepoimento(parseInt(t.getAttribute('data-indice'), 10));
          reiniciarAutoplayDepoimentos();
        });
      });

      if (slides.length > 1) reiniciarAutoplayDepoimentos();
    }
  });
</script>
