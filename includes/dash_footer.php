  </main>
</div>
<script>
  (function(){
    var toggle = document.getElementById('sidebarToggle');
    var links = document.getElementById('sidebarLinks');
    if (toggle && links) {
      toggle.addEventListener('click', function(){ links.classList.toggle('aberta'); });
    }
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
<script src="<?= BASE_URL ?>/assets/js/efeitos-premium.js"></script>
</body>
</html>
