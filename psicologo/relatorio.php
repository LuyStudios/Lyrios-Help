<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('psicologo');
$titulo = "Relatório e Receita"; $pagina = 'relatorio'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];
$erro = ''; $sucesso = '';

$totalGanho = $pdo->prepare("SELECT COALESCE(SUM(p.valor_psicologo),0) s FROM pagamentos p JOIN consultas c ON c.id=p.consulta_id WHERE c.psicologo_id=? AND p.estado='pago'");
$totalGanho->execute([$uid]); $totalGanho = (float)$totalGanho->fetch()['s'];

$totalLevantado = $pdo->prepare("SELECT COALESCE(SUM(valor),0) s FROM levantamentos WHERE psicologo_id=? AND estado != 'rejeitado'");
$totalLevantado->execute([$uid]); $totalLevantado = (float)$totalLevantado->fetch()['s'];

$saldoDisponivel = $totalGanho - $totalLevantado;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $valor = (float)($_POST['valor'] ?? 0);
    $referencia = trim($_POST['referencia_bancaria'] ?? '');

    if ($valor <= 0 || $valor > $saldoDisponivel) {
        $erro = 'Indica um valor válido, até ao limite do teu saldo disponível.';
    } elseif ($referencia === '') {
        $erro = 'Indica a referência bancária / IBAN para receberes o valor.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO levantamentos (psicologo_id, valor, referencia_bancaria) VALUES (?,?,?)");
        $stmt->execute([$uid, $valor, $referencia]);
        registarAtividade($pdo, $uid, 'Pediu o levantamento de ' . formatarKz($valor) . '.');
        $sucesso = 'Pedido de levantamento enviado. O administrador vai processá-lo em breve.';
        $saldoDisponivel -= $valor;
    }
}

$consultasPorMes = $pdo->prepare("
    SELECT DATE_FORMAT(c.data_hora, '%Y-%m') mes, COUNT(*) total, COALESCE(SUM(p.valor_psicologo),0) receita
    FROM consultas c LEFT JOIN pagamentos p ON p.consulta_id = c.id AND p.estado='pago'
    WHERE c.psicologo_id = ? AND c.estado = 'concluida'
    GROUP BY mes ORDER BY mes DESC LIMIT 12
");
$consultasPorMes->execute([$uid]);
$consultasPorMes = $consultasPorMes->fetchAll();

$levantamentos = $pdo->prepare("SELECT * FROM levantamentos WHERE psicologo_id = ? ORDER BY criado_em DESC");
$levantamentos->execute([$uid]);
$levantamentos = $levantamentos->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Relatório e Receita</h1>
<div class="stat-cards" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card"><div class="value"><?= formatarKz($totalGanho) ?></div><div class="label">Total ganho (histórico)</div></div>
  <div class="stat-card"><div class="value"><?= formatarKz($totalLevantado) ?></div><div class="label">Já levantado</div></div>
  <div class="stat-card"><div class="value"><?= formatarKz($saldoDisponivel) ?></div><div class="label">Saldo disponível para levantar</div></div>
</div>

<div class="card" style="margin-bottom:22px;max-width:480px;">
  <h3>Pedir levantamento</h3>
  <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Valor a levantar (Kz)</label><input type="number" name="valor" max="<?= $saldoDisponivel ?>" min="1" step="0.01" required></div>
    <div class="form-group"><label>IBAN / referência bancária</label><input type="text" name="referencia_bancaria" required></div>
    <button class="btn btn-primary btn-full" type="submit">Pedir levantamento</button>
  </form>
</div>

<div class="card" style="margin-bottom:22px;">
  <h3>Receita por mês</h3>
  <table>
    <tr><th>Mês</th><th>Consultas concluídas</th><th>Receita (após comissão)</th></tr>
    <?php foreach ($consultasPorMes as $m): ?>
    <tr><td><?= date('m/Y', strtotime($m['mes'] . '-01')) ?></td><td><?= $m['total'] ?></td><td><?= formatarKz($m['receita']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($consultasPorMes)): ?><tr><td colspan="3">Ainda sem dados suficientes.</td></tr><?php endif; ?>
  </table>
</div>

<div class="card">
  <h3>Histórico de levantamentos</h3>
  <table>
    <tr><th>Valor</th><th>Referência</th><th>Estado</th><th>Data</th></tr>
    <?php foreach ($levantamentos as $l): ?>
    <tr>
      <td><?= formatarKz($l['valor']) ?></td>
      <td><?= escape($l['referencia_bancaria']) ?></td>
      <td><span class="badge badge-<?= $l['estado']==='pago'?'confirmada':($l['estado']==='rejeitado'?'cancelada':'pendente') ?>"><?= ucfirst($l['estado']) ?></span></td>
      <td><?= date('d/m/Y', strtotime($l['criado_em'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($levantamentos)): ?><tr><td colspan="4">Ainda não pediste nenhum levantamento.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
