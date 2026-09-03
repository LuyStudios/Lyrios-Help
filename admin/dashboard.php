<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('admin');
$titulo = "Painel Admin"; $pagina = 'dashboard'; $areaTipo = 'admin';

$totalPacientes = $pdo->query("SELECT COUNT(*) c FROM utilizadores WHERE tipo='paciente'")->fetch()['c'];
$totalPsicologos = $pdo->query("SELECT COUNT(*) c FROM utilizadores WHERE tipo='psicologo'")->fetch()['c'];
$totalConsultas = $pdo->query("SELECT COUNT(*) c FROM consultas")->fetch()['c'];
$receitaPlataforma = $pdo->query("SELECT COALESCE(SUM(valor_plataforma),0) s FROM pagamentos WHERE estado='pago'")->fetch()['s'];
$receitaTotal = $pdo->query("SELECT COALESCE(SUM(valor_total),0) s FROM pagamentos WHERE estado='pago'")->fetch()['s'];
$pendentesAprovacao = $pdo->query("SELECT COUNT(*) c FROM utilizadores WHERE tipo='psicologo' AND estado='pendente'")->fetch()['c'];

$ultimasConsultas = $pdo->query("
    SELECT c.*, up.nome AS paciente, us.nome AS psicologo, p.valor_total
    FROM consultas c
    JOIN utilizadores up ON up.id = c.paciente_id
    JOIN utilizadores us ON us.id = c.psicologo_id
    LEFT JOIN pagamentos p ON p.consulta_id = c.id
    ORDER BY c.criado_em DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Painel de Administração</h1>
<div class="stat-cards">
  <div class="stat-card"><div class="value"><?= $totalPacientes ?></div><div class="label">Pacientes</div></div>
  <div class="stat-card"><div class="value"><?= $totalPsicologos ?></div><div class="label">Psicólogos</div></div>
  <div class="stat-card"><div class="value"><?= $totalConsultas ?></div><div class="label">Consultas realizadas</div></div>
  <div class="stat-card"><div class="value"><?= formatarKz($receitaPlataforma) ?></div><div class="label">Receita da plataforma</div></div>
</div>
<div class="stat-cards" style="grid-template-columns:repeat(2,1fr);">
  <div class="stat-card"><div class="value"><?= formatarKz($receitaTotal) ?></div><div class="label">Volume total transacionado</div></div>
  <div class="stat-card"><div class="value"><?= $pendentesAprovacao ?></div><div class="label">Psicólogos aguardando aprovação</div></div>
</div>

<?php if ($pendentesAprovacao > 0): ?>
<div class="alert alert-error">
  Tens <?= $pendentesAprovacao ?> psicólogo(s) por aprovar. <a href="<?= BASE_URL ?>/admin/utilizadores.php?tipo=psicologo" style="font-weight:700;">Rever agora</a>
</div>
<?php endif; ?>

<div class="card">
  <h3>Últimas consultas</h3>
  <table>
    <tr><th>Paciente</th><th>Psicólogo</th><th>Data</th><th>Valor</th><th>Estado</th></tr>
    <?php foreach ($ultimasConsultas as $c): ?>
    <tr>
      <td><?= escape($c['paciente']) ?></td>
      <td><?= escape($c['psicologo']) ?></td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><?= $c['valor_total'] ? formatarKz($c['valor_total']) : '-' ?></td>
      <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
