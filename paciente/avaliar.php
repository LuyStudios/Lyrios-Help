<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Avaliar Consulta"; $pagina = 'consultas'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$consultaId = validarInteiro($_GET['consulta_id'] ?? ($_POST['consulta_id'] ?? 0), 1, null, 0);

$stmt = $pdo->prepare("SELECT c.*, u.nome AS nome_psicologo FROM consultas c JOIN utilizadores u ON u.id = c.psicologo_id WHERE c.id = ? AND c.paciente_id = ?");
$stmt->execute([$consultaId, $uid]);
$consulta = $stmt->fetch();

if (!$consulta) { die('Consulta não encontrada.'); }
if ($consulta['estado'] !== 'concluida') { die('Só podes avaliar consultas já concluídas.'); }

$stmt = $pdo->prepare("SELECT * FROM avaliacoes WHERE consulta_id = ?");
$stmt->execute([$consultaId]);
$avaliacaoExistente = $stmt->fetch();

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$avaliacaoExistente) {
    csrfVerificar();
    $nota = validarInteiro($_POST['nota'] ?? 0, 1, 5, 0);
    $comentario = trim($_POST['comentario'] ?? '');

    if ($nota < 1) {
        $erro = 'Escolhe uma nota entre 1 e 5 estrelas.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO avaliacoes (consulta_id, psicologo_id, paciente_id, nota, comentario) VALUES (?,?,?,?,?)");
        $stmt->execute([$consultaId, $consulta['psicologo_id'], $uid, $nota, mb_substr($comentario, 0, 500)]);
        registarAtividade($pdo, $uid, 'Avaliou a consulta com ' . $consulta['nome_psicologo'] . '.');
        registarAtividade($pdo, $consulta['psicologo_id'], 'Recebeu uma nova avaliação de um paciente.');
        header('Location: ' . BASE_URL . '/paciente/minhas_consultas.php?avaliado=1');
        exit;
    }
}

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Avaliar Consulta</h1>
<div class="card" style="max-width:520px;">
  <p>Consulta com <strong><?= escape($consulta['nome_psicologo']) ?></strong> em <?= date('d/m/Y', strtotime($consulta['data_hora'])) ?></p>

  <?php if ($avaliacaoExistente): ?>
    <div class="alert alert-success">Já avaliaste esta consulta.</div>
    <div style="font-size:22px;"><?= estrelasHtml($avaliacaoExistente['nota']) ?></div>
    <?php if ($avaliacaoExistente['comentario']): ?><p style="margin-top:10px;color:#697871;">"<?= escape($avaliacaoExistente['comentario']) ?>"</p><?php endif; ?>
  <?php else: ?>
    <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
    <form method="post">
      <?php csrfCampo(); ?>
      <input type="hidden" name="consulta_id" value="<?= $consultaId ?>">
      <div class="form-group">
        <label>A tua nota</label>
        <div class="seletor-estrelas" style="font-size:30px;color:#e7ebe8;cursor:pointer;">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-solid fa-star" data-valor="<?= $i ?>" style="margin-right:4px;"></i>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="nota" id="notaEscolhida" value="0" required>
      </div>
      <div class="form-group"><label>Comentário (opcional)</label><textarea name="comentario" maxlength="500" placeholder="Conta como foi a tua experiência..."></textarea></div>
      <button class="btn btn-primary btn-full" type="submit">Enviar avaliação</button>
    </form>
    <script>
      (function(){
        var estrelas = document.querySelectorAll('.seletor-estrelas i');
        var campo = document.getElementById('notaEscolhida');
        estrelas.forEach(function(estrela){
          estrela.addEventListener('click', function(){
            var valor = parseInt(this.getAttribute('data-valor'));
            campo.value = valor;
            estrelas.forEach(function(e){
              e.style.color = parseInt(e.getAttribute('data-valor')) <= valor ? '#c8843e' : '#e7ebe8';
            });
          });
        });
      })();
    </script>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
