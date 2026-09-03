<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('psicologo');
$titulo = "Historial do Paciente"; $pagina = 'agenda'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];

$pacienteId = validarInteiro($_GET['paciente_id'] ?? 0, 1, null, 0);

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE paciente_id = ? AND psicologo_id = ?");
$stmt->execute([$pacienteId, $uid]);
if ($stmt->fetch()['c'] == 0) { die('Só podes ver o historial de pacientes que já tenham marcado consulta contigo.'); }

$stmt = $pdo->prepare("SELECT nome, email, telefone, criado_em FROM utilizadores WHERE id = ? AND tipo='paciente'");
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();
if (!$paciente) { die('Paciente não encontrado.'); }

$consultas = $pdo->prepare("
    SELECT c.*, s.nome AS nome_servico, a.nota
    FROM consultas c
    LEFT JOIN servicos s ON s.id = c.servico_id
    LEFT JOIN avaliacoes a ON a.consulta_id = c.id
    WHERE c.paciente_id = ? AND c.psicologo_id = ?
    ORDER BY c.data_hora DESC
");
$consultas->execute([$pacienteId, $uid]);
$consultas = $consultas->fetchAll();

$respostas = $pdo->prepare("
    SELECT pq.texto, rq.resposta FROM respostas_questionario rq
    JOIN perguntas_questionario pq ON pq.id = rq.pergunta_id
    WHERE rq.paciente_id = ? ORDER BY pq.ordem ASC
");
$respostas->execute([$pacienteId]);
$respostas = $respostas->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Historial de <?= escape($paciente['nome']) ?></h1>

<?php if (!empty($respostas)): ?>
<div class="card" style="margin-bottom:22px;">
  <h3>Questionário inicial do paciente</h3>
  <?php foreach ($respostas as $r): ?>
    <p style="margin-bottom:4px;"><strong><?= escape($r['texto']) ?></strong></p>
    <p style="color:#697871;margin-top:0;"><?= escape($r['resposta']) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <h3>Consultas realizadas com este paciente</h3>
  <table>
    <tr><th>Data</th><th>Serviço</th><th>Estado</th><th>Avaliação recebida</th></tr>
    <?php foreach ($consultas as $c): ?>
    <tr>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><?= escape($c['nome_servico'] ?? 'Geral') ?></td>
      <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
      <td><?= $c['nota'] ? estrelasHtml((int)$c['nota']) : '-' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
