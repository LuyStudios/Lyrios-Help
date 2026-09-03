<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('admin');
$titulo = "Consultas"; $pagina = 'consultas'; $areaTipo = 'admin';

$consultas = $pdo->query("
    SELECT c.*, up.nome AS paciente, us.nome AS psicologo, p.valor_total, p.estado AS estado_pagamento
    FROM consultas c
    JOIN utilizadores up ON up.id = c.paciente_id
    JOIN utilizadores us ON us.id = c.psicologo_id
    LEFT JOIN pagamentos p ON p.consulta_id = c.id
    ORDER BY c.data_hora DESC
")->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Todas as Consultas</h1>
<div class="card">
  <table>
    <tr><th>Paciente</th><th>Psicólogo</th><th>Data</th><th>Valor</th><th>Pagamento</th><th>Estado</th></tr>
    <?php foreach ($consultas as $c): ?>
    <tr>
      <td><?= escape($c['paciente']) ?></td>
      <td><?= escape($c['psicologo']) ?></td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><?= $c['valor_total'] ? formatarKz($c['valor_total']) : '-' ?></td>
      <td><span class="badge badge-<?= $c['estado_pagamento']=='pago'?'pago':'pendente' ?>"><?= ucfirst($c['estado_pagamento'] ?? 'pendente') ?></span></td>
      <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($consultas)): ?><tr><td colspan="6">Sem consultas registadas.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
