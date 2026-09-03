/**
 * Efeitos premium partilhados por todo o site: barra de progresso de scroll,
 * navbar reativa, botão flutuante de ação, onda (ripple) ao clicar em botões,
 * e tilt magnético subtil na imagem do herói.
 */
document.addEventListener('DOMContentLoaded', function () {
  var reduzirMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---- Barra de progresso de scroll ---- */
  var barra = document.createElement('div');
  barra.className = 'progresso-scroll';
  document.body.appendChild(barra);
  function atualizarProgresso() {
    var alturaTotal = document.documentElement.scrollHeight - window.innerHeight;
    var progresso = alturaTotal > 0 ? (window.scrollY / alturaTotal) * 100 : 0;
    barra.style.width = progresso + '%';
  }

  /* ---- Navbar encolhe/intensifica o vidro ao rolar ---- */
  var navbar = document.querySelector('.navbar');

  /* ---- Botão "voltar ao topo" ---- */
  var topoBtn = document.createElement('button');
  topoBtn.className = 'topo-flutuante';
  topoBtn.setAttribute('aria-label', 'Voltar ao topo');
  topoBtn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
  topoBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: reduzirMovimento ? 'auto' : 'smooth' });
  });
  document.body.appendChild(topoBtn);

  function aoRolar() {
    atualizarProgresso();
    var passouHero = window.scrollY > 420;
    if (navbar) navbar.classList.toggle('encolhida', window.scrollY > 30);
    topoBtn.classList.toggle('visivel', passouHero);
    var ctaFlutuante = document.querySelector('.cta-flutuante');
    if (ctaFlutuante) ctaFlutuante.classList.toggle('visivel', passouHero);
  }
  window.addEventListener('scroll', aoRolar, { passive: true });
  aoRolar();

  /* ---- Onda (ripple) ao clicar em qualquer botão .btn ---- */
  document.querySelectorAll('.btn').forEach(function (botao) {
    botao.addEventListener('click', function (e) {
      var raio = Math.max(botao.offsetWidth, botao.offsetHeight);
      var onda = document.createElement('span');
      var rect = botao.getBoundingClientRect();
      onda.className = 'ripple';
      onda.style.width = onda.style.height = raio + 'px';
      onda.style.left = (e.clientX - rect.left - raio / 2) + 'px';
      onda.style.top = (e.clientY - rect.top - raio / 2) + 'px';
      botao.appendChild(onda);
      setTimeout(function () { onda.remove(); }, 650);
    });
  });

  /* ---- Revelação + tilt magnético subtil na imagem do herói ---- */
  var moldura = document.querySelector('.hero-imagem-moldura');
  if (moldura) {
    requestAnimationFrame(function () { moldura.classList.add('animar-entrada'); });

    if (!reduzirMovimento && window.matchMedia('(hover: hover)').matches) {
      var visual = document.querySelector('.hero-visual');
      visual.addEventListener('mousemove', function (e) {
        var rect = moldura.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;
        moldura.style.transform = 'rotateY(' + (x * 8) + 'deg) rotateX(' + (y * -8) + 'deg)';
      });
      visual.addEventListener('mouseleave', function () {
        moldura.style.transform = 'rotateY(0deg) rotateX(0deg)';
      });
    }
  }
});
