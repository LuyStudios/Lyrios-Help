<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Minhas Consultas"; $pagina = 'consultas'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("
    SELECT c.*, u.nome AS nome_psicologo, s.nome AS nome_servico, p.valor_total, p.estado AS estado_pagamento,
        (SELECT COUNT(*) FROM avaliacoes a WHERE a.consulta_id = c.id) AS ja_avaliada
    FROM consultas c
    JOIN utilizadores u ON u.id = c.psicologo_id
    LEFT JOIN servicos s ON s.id = c.servico_id
    LEFT JOIN pagamentos p ON p.consulta_id = c.id
    WHERE c.paciente_id = ?
    ORDER BY c.data_hora DESC
");
$stmt->execute([$uid]);
$consultas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Minhas Consultas</h1>
<?php if (isset($_GET['sucesso'])): ?><div class="alert alert-success">Consulta marcada e pagamento efetuado com sucesso!</div><?php endif; ?>
<?php if (isset($_GET['avaliado'])): ?><div class="alert alert-success">Avaliação enviada. Obrigado pelo teu feedback!</div><?php endif; ?>
<?php if (isset($_GET['remarcada'])): ?><div class="alert alert-success">Consulta remarcada com sucesso!</div><?php endif; ?>
<div class="card">
  <table>
    <tr><th>Psicólogo</th><th>Serviço</th><th>Data</th><th>Valor</th><th>Pagamento</th><th>Estado</th><th>Ações</th></tr>
    <?php foreach ($consultas as $c): ?>
    <tr>
      <td><?= escape($c['nome_psicologo']) ?></td>
      <td><?= escape($c['nome_servico'] ?? 'Geral') ?></td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><?= $c['valor_total'] ? formatarKz($c['valor_total']) : '-' ?></td>
      <td><span class="badge badge-<?= $c['estado_pagamento'] === 'pago' ? 'pago' : 'pendente' ?>"><?= ucfirst($c['estado_pagamento'] ?? 'pendente') ?></span></td>
      <td>
        <span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span>
        <?php if ($c['estado'] === 'pendente' && $c['estado_pagamento'] === 'pago'): ?>
          <div style="font-size:11px;color:#9a6a12;margin-top:3px;">Aguarda confirmação do psicólogo</div>
        <?php endif; ?>
      </td>
      <td style="display:flex;gap:6px;flex-wrap:wrap;">
        <?php if ($c['estado'] === 'confirmada'): ?>
          <a href="<?= BASE_URL ?>/chamada/sala.php?codigo=<?= urlencode($c['sala_codigo']) ?>" class="btn btn-small btn-primary">Entrar</a>
        <?php endif; ?>
        <?php if (in_array($c['estado'], ['pendente', 'confirmada'], true)): ?>
          <a href="<?= BASE_URL ?>/paciente/remarcar.php?consulta_id=<?= $c['id'] ?>" class="btn btn-small btn-outline" style="color:#1f6f5c;border:1px solid #1f6f5c;">Remarcar</a>
        <?php endif; ?>
        <?php if ($c['estado'] === 'concluida'): ?>
          <a href="<?= BASE_URL ?>/paciente/avaliar.php?consulta_id=<?= $c['id'] ?>" class="btn btn-small btn-outline" style="color:#c8843e;border:1px solid #c8843e;">
            <?= $c['ja_avaliada'] > 0 ? 'Ver avaliação' : 'Avaliar' ?>
          </a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($consultas)): ?><tr><td colspan="7">Ainda não tens consultas marcadas.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
