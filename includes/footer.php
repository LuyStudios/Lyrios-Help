<footer>
  <div class="container">
    <div class="footer-topo">
      <h3>Cada consulta é um passo dado com coragem.</h3>
      <form class="footer-form" method="post" action="<?= BASE_URL ?>/contactos.php">
        <?php csrfCampo(); ?>
        <input type="hidden" name="assunto" value="Contacto rápido (rodapé)">
        <input type="text" name="nome" placeholder="O teu nome" required>
        <input type="email" name="email" placeholder="O teu email" required>
        <input type="text" name="mensagem" placeholder="Como podemos ajudar?" required>
        <button class="btn btn-primary" type="submit" style="align-self:flex-start;">Enviar mensagem</button>
      </form>
    </div>
    <div class="grid grid-4">
      <div>
        <div class="marca-lyrios">
          <img src="<?= BASE_URL ?>/assets/img/logo-icone-grande.png" alt="Lyrios">
          <span class="texto-marca">Lyrios</span>
        </div>
        <a href="<?= BASE_URL ?>/historia.php">Sobre nós</a>
        <a href="<?= BASE_URL ?>/beneficios.php">Benefícios</a>
        <a href="<?= BASE_URL ?>/servicos.php">Serviços</a>
        <a href="<?= BASE_URL ?>/parceiros.php">Parceiros</a>
      </div>
      <div>
        <h4>Apoio</h4>
        <a href="<?= BASE_URL ?>/apoios.php">Linhas de apoio</a>
        <a href="<?= BASE_URL ?>/perguntas_frequentes.php">Perguntas Frequentes</a>
        <a href="<?= BASE_URL ?>/contactos.php">Contactos</a>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=psicologo">Trabalhar connosco</a>
      </div>
      <div>
        <h4>Conta</h4>
        <a href="<?= BASE_URL ?>/auth/registar.php?tipo=paciente">Criar conta de paciente</a>
        <a href="<?= BASE_URL ?>/auth/login.php">Iniciar sessão</a>
      </div>
      <div>
        <h4>Contactos</h4>
        <a>Luanda, Angola</a>
        <a>info@lyrios.co.ao</a>
        <a>+244 900 000 000</a>
        <div class="footer-redes">
          <a href="#" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
          <a href="#" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?= date('Y') ?> Lyrios. Todos os direitos reservados.
    </div>
  </div>
</footer>
</body>
</html>
