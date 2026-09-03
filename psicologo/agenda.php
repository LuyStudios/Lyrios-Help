<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('psicologo');
$titulo = "Agenda"; $pagina = 'agenda'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrfVerificar(); }
if (isset($_POST['confirmar'])) {
    $stmt = $pdo->prepare("
        SELECT c.id FROM consultas c JOIN pagamentos p ON p.consulta_id = c.id
        WHERE c.id = ? AND c.psicologo_id = ? AND p.estado = 'pago'
    ");
    $stmt->execute([(int)$_POST['consulta_id'], $uid]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE consultas SET estado='confirmada' WHERE id=? AND psicologo_id=?")->execute([(int)$_POST['consulta_id'], $uid]);
        registarAtividade($pdo, $uid, 'Confirmou a disponibilidade para uma consulta.');
    }
}
if (isset($_POST['marcar_concluida'])) {
    $stmt = $pdo->prepare("UPDATE consultas SET estado='concluida' WHERE id=? AND psicologo_id=?");
    $stmt->execute([(int)$_POST['consulta_id'], $uid]);
    registarAtividade($pdo, $uid, 'Marcou uma consulta como concluída.');
}
if (isset($_POST['cancelar'])) {
    $stmt = $pdo->prepare("UPDATE consultas SET estado='cancelada' WHERE id=? AND psicologo_id=?");
    $stmt->execute([(int)$_POST['consulta_id'], $uid]);
    registarAtividade($pdo, $uid, 'Cancelou uma consulta.');
}

$stmt = $pdo->prepare("
    SELECT c.*, u.nome AS nome_paciente, u.telefone, p.estado AS estado_pagamento
    FROM consultas c JOIN utilizadores u ON u.id = c.paciente_id
    LEFT JOIN pagamentos p ON p.consulta_id = c.id
    WHERE c.psicologo_id = ? ORDER BY c.data_hora DESC
");
$stmt->execute([$uid]);
$consultas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Agenda de Consultas</h1>
<div class="card">
  <table>
    <tr><th>Paciente</th><th>Contacto</th><th>Data</th><th>Pagamento</th><th>Estado</th><th>Ações</th></tr>
    <?php foreach ($consultas as $c):
        $aguardandoConfirmacao = $c['estado'] === 'pendente' && $c['estado_pagamento'] === 'pago';
    ?>
    <tr>
      <td><?= escape($c['nome_paciente']) ?> <a href="<?= BASE_URL ?>/psicologo/historial_paciente.php?paciente_id=<?= $c['paciente_id'] ?>" title="Ver historial" style="color:#1f6f5c;"><i class="fa-solid fa-clock-rotate-left"></i></a></td>
      <td><?= escape($c['telefone']) ?></td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><span class="badge badge-<?= $c['estado_pagamento']==='pago'?'pago':'pendente' ?>"><?= ucfirst($c['estado_pagamento'] ?? 'pendente') ?></span></td>
      <td>
        <span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span>
        <?php if ($aguardandoConfirmacao): ?><div style="font-size:11.5px;color:#9a6a12;margin-top:3px;">Aguarda a tua confirmação</div><?php endif; ?>
      </td>
      <td style="display:flex;gap:6px;flex-wrap:wrap;">
        <?php if ($c['estado'] === 'confirmada'): ?>
          <a href="<?= BASE_URL ?>/chamada/sala.php?codigo=<?= urlencode($c['sala_codigo']) ?>" class="btn btn-small btn-primary">Entrar</a>
          <form method="post"><?php csrfCampo(); ?><input type="hidden" name="consulta_id" value="<?= $c['id'] ?>"><button name="marcar_concluida" class="btn btn-small btn-outline" style="color:#1f6f5c;border:1px solid #1f6f5c;">Concluir</button></form>
          <form method="post"><?php csrfCampo(); ?><input type="hidden" name="consulta_id" value="<?= $c['id'] ?>"><button name="cancelar" class="btn btn-small btn-danger">Cancelar</button></form>
        <?php elseif ($aguardandoConfirmacao): ?>
          <form method="post"><?php csrfCampo(); ?><input type="hidden" name="consulta_id" value="<?= $c['id'] ?>"><button name="confirmar" class="btn btn-small btn-primary">Confirmar disponibilidade</button></form>
          <form method="post"><?php csrfCampo(); ?><input type="hidden" name="consulta_id" value="<?= $c['id'] ?>"><button name="cancelar" class="btn btn-small btn-danger">Recusar</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($consultas)): ?><tr><td colspan="6">Sem consultas.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
