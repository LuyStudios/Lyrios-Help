<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('psicologo');
$titulo = "Painel do Psicólogo"; $pagina = 'dashboard'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];

$total = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE psicologo_id = ?");
$total->execute([$uid]); $total = $total->fetch()['c'];

$proximas = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE psicologo_id = ? AND data_hora >= NOW() AND estado != 'cancelada'");
$proximas->execute([$uid]); $proximas = $proximas->fetch()['c'];

$pacientesUnicos = $pdo->prepare("SELECT COUNT(DISTINCT paciente_id) c FROM consultas WHERE psicologo_id = ?");
$pacientesUnicos->execute([$uid]); $pacientesUnicos = $pacientesUnicos->fetch()['c'];

$ganhos = $pdo->prepare("SELECT COALESCE(SUM(p.valor_psicologo),0) s FROM pagamentos p JOIN consultas c ON c.id=p.consulta_id WHERE c.psicologo_id=? AND p.estado='pago'");
$ganhos->execute([$uid]); $ganhos = $ganhos->fetch()['s'];

$avaliacao = mediaAvaliacoes($pdo, $uid);

$status = $pdo->prepare("SELECT status_personalizado FROM perfis_psicologos WHERE utilizador_id = ?");
$status->execute([$uid]); $status = $status->fetch()['status_personalizado'];

$aguardandoConfirmacao = $pdo->prepare("
    SELECT COUNT(*) c FROM consultas cn JOIN pagamentos p ON p.consulta_id = cn.id
    WHERE cn.psicologo_id = ? AND cn.estado = 'pendente' AND p.estado = 'pago'
");
$aguardandoConfirmacao->execute([$uid]);
$aguardandoConfirmacao = $aguardandoConfirmacao->fetch()['c'];

$proximasConsultas = $pdo->prepare("SELECT c.*, u.nome AS nome_paciente, u.foto AS foto_paciente FROM consultas c JOIN utilizadores u ON u.id=c.paciente_id WHERE c.psicologo_id=? AND c.data_hora >= NOW() ORDER BY c.data_hora ASC LIMIT 5");
$proximasConsultas->execute([$uid]);
$proximasConsultas = $proximasConsultas->fetchAll();

$hora = (int)date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 19 ? 'Boa tarde' : 'Boa noite');
$primeiroNome = explode(' ', trim($_SESSION['nome']))[0];

require_once __DIR__ . '/../includes/dash_header.php';
?>
<div style="margin-bottom:30px;">
  <p style="color:var(--muted);font-size:13.5px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;"><?= escape($saudacao) ?></p>
  <h1 style="margin-bottom:0;">Olá, Dr(a). <?= escape($primeiroNome) ?></h1>
</div>

<?php if ($aguardandoConfirmacao > 0): ?>
<a href="<?= BASE_URL ?>/psicologo/agenda.php" class="card" style="display:flex;align-items:center;gap:16px;margin-bottom:24px;border-color:#f0b878;background:#fdf6ec;">
  <div style="width:42px;height:42px;border-radius:50%;background:#fdf1dd;color:#95650f;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;"><i class="fa-solid fa-bell"></i></div>
  <div style="flex:1;">
    <strong style="color:#95650f;"><?= $aguardandoConfirmacao ?> consulta<?= $aguardandoConfirmacao > 1 ? 's' : '' ?> à espera da tua confirmação</strong>
    <p style="margin:2px 0 0;color:#95650f;opacity:.85;font-size:13px;">O pagamento já foi feito — confirma a tua disponibilidade para a chamada ficar disponível.</p>
  </div>
  <i class="fa-solid fa-arrow-right" style="color:#95650f;"></i>
</a>
<?php endif; ?>

<?php if ($status): ?>
<div class="card" style="margin-bottom:24px;display:flex;align-items:center;gap:10px;">
  <i class="fa-solid fa-circle" style="font-size:9px;color:var(--success);"></i>
  <div><strong>O teu status atual:</strong> <?= escape($status) ?> <a href="<?= BASE_URL ?>/psicologo/perfil.php" style="color:var(--primary);font-size:13px;margin-left:8px;">Editar</a></div>
</div>
<?php endif; ?>

<div class="grid grid-3" style="margin-bottom:32px;">
  <a href="<?= BASE_URL ?>/psicologo/agenda.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-calendar-days"></i></div>
    <h3 style="font-size:15px;">Agenda</h3>
  </a>
  <a href="<?= BASE_URL ?>/psicologo/mensagens.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-comments"></i></div>
    <h3 style="font-size:15px;">Mensagens</h3>
  </a>
  <a href="<?= BASE_URL ?>/psicologo/relatorio.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-chart-line"></i></div>
    <h3 style="font-size:15px;">Relatório e Receita</h3>
  </a>
</div>

<div class="stat-cards">
  <div class="stat-card"><div class="value"><?= $total ?></div><div class="label">Total de consultas</div></div>
  <div class="stat-card"><div class="value"><?= $proximas ?></div><div class="label">Consultas próximas</div></div>
  <div class="stat-card"><div class="value"><?= $pacientesUnicos ?></div><div class="label">Pacientes acompanhados</div></div>
  <div class="stat-card"><div class="value"><?= formatarKz($ganhos) ?></div><div class="label">Ganhos totais (após comissão)</div></div>
</div>

<div class="card" style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
  <div>
    <h3 style="margin-bottom:6px;">A tua avaliação</h3>
    <div style="font-size:20px;"><?= estrelasHtml($avaliacao['media'], $avaliacao['total']) ?></div>
  </div>
</div>

<div class="card">
  <h3>Próximas consultas</h3>
  <table>
    <tr><th>Paciente</th><th>Data</th><th>Estado</th><th>Ação</th></tr>
    <?php foreach ($proximasConsultas as $c): ?>
    <tr>
      <td style="display:flex;align-items:center;gap:10px;">
        <?php if ($c['foto_paciente']): ?>
          <img src="<?= BASE_URL ?>/<?= escape($c['foto_paciente']) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
        <?php else: ?>
          <div style="width:30px;height:30px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-solid fa-user"></i></div>
        <?php endif; ?>
        <?= escape($c['nome_paciente']) ?>
      </td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
      <td><?php if ($c['estado'] === 'confirmada'): ?><a href="<?= BASE_URL ?>/chamada/sala.php?codigo=<?= urlencode($c['sala_codigo']) ?>" class="btn btn-small btn-primary">Entrar</a><?php else: ?>-<?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($proximasConsultas)): ?><tr><td colspan="4" style="text-align:center;padding:30px 0;color:var(--muted);">Sem consultas agendadas de momento.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
