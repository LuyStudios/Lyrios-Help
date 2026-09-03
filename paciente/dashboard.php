<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Painel do Paciente"; $pagina = 'dashboard'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$totalConsultas = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE paciente_id = ?");
$totalConsultas->execute([$uid]);
$totalConsultas = $totalConsultas->fetch()['c'];

$proximas = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE paciente_id = ? AND data_hora >= NOW() AND estado != 'cancelada'");
$proximas->execute([$uid]);
$proximas = $proximas->fetch()['c'];

$concluidas = $pdo->prepare("SELECT COUNT(*) c FROM consultas WHERE paciente_id = ? AND estado = 'concluida'");
$concluidas->execute([$uid]);
$concluidas = $concluidas->fetch()['c'];

$gasto = $pdo->prepare("SELECT COALESCE(SUM(p.valor_total),0) s FROM pagamentos p JOIN consultas c ON c.id = p.consulta_id WHERE c.paciente_id = ? AND p.estado='pago'");
$gasto->execute([$uid]);
$gasto = $gasto->fetch()['s'];

$proximasConsultas = $pdo->prepare("SELECT c.*, u.nome AS nome_psicologo, u.foto AS foto_psicologo FROM consultas c JOIN utilizadores u ON u.id = c.psicologo_id WHERE c.paciente_id = ? AND c.data_hora >= NOW() ORDER BY c.data_hora ASC LIMIT 5");
$proximasConsultas->execute([$uid]);
$proximasConsultas = $proximasConsultas->fetchAll();

$proximaEmDestaque = $proximasConsultas[0] ?? null;
$restantes = $proximaEmDestaque ? array_slice($proximasConsultas, 1) : [];

$hora = (int)date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 19 ? 'Boa tarde' : 'Boa noite');
$primeiroNome = explode(' ', trim($_SESSION['nome']))[0];

require_once __DIR__ . '/../includes/dash_header.php';
?>
<div style="margin-bottom:34px;">
  <p style="color:var(--muted);font-size:13.5px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;"><?= escape($saudacao) ?></p>
  <h1 style="margin-bottom:0;"><?= escape($primeiroNome) ?>, como te sentes hoje?</h1>
</div>

<div class="grid grid-3" style="margin-bottom:32px;">
  <a href="<?= BASE_URL ?>/paciente/buscar_psicologos.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3 style="font-size:15px;">Procurar Psicólogo</h3>
  </a>
  <a href="<?= BASE_URL ?>/paciente/marcar_consulta.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-calendar-plus"></i></div>
    <h3 style="font-size:15px;">Marcar Consulta</h3>
  </a>
  <a href="<?= BASE_URL ?>/paciente/mensagens.php" class="card" style="text-align:center;padding:26px 20px;">
    <div class="icon" style="margin:0 auto 12px;"><i class="fa-solid fa-comments"></i></div>
    <h3 style="font-size:15px;">Mensagens</h3>
  </a>
</div>

<div class="stat-cards">
  <div class="stat-card"><div class="value"><?= $totalConsultas ?></div><div class="label">Total de consultas</div></div>
  <div class="stat-card"><div class="value"><?= $proximas ?></div><div class="label">Consultas próximas</div></div>
  <div class="stat-card"><div class="value"><?= $concluidas ?></div><div class="label">Consultas concluídas</div></div>
  <div class="stat-card"><div class="value"><?= formatarKz($gasto) ?></div><div class="label">Total investido em ti</div></div>
</div>

<?php if ($proximaEmDestaque): ?>
<div class="card" style="background:linear-gradient(120deg,var(--primary-dark),var(--primary-darker));color:#fff;border:none;margin-bottom:28px;display:flex;align-items:center;gap:22px;flex-wrap:wrap;">
  <?php if ($proximaEmDestaque['foto_psicologo']): ?>
    <img src="<?= BASE_URL ?>/<?= escape($proximaEmDestaque['foto_psicologo']) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.3);flex-shrink:0;">
  <?php else: ?>
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;"><i class="fa-solid fa-user"></i></div>
  <?php endif; ?>
  <div style="flex:1;min-width:180px;">
    <p style="color:rgba(255,255,255,.6);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">A tua próxima consulta</p>
    <h3 style="color:#fff;margin-bottom:4px;font-size:19px;"><?= escape($proximaEmDestaque['nome_psicologo']) ?></h3>
    <p style="color:rgba(255,255,255,.75);font-size:14px;margin:0;"><i class="fa-regular fa-clock"></i> <?= date('d/m/Y \à\s H:i', strtotime($proximaEmDestaque['data_hora'])) ?></p>
  </div>
  <?php if ($proximaEmDestaque['estado'] === 'confirmada'): ?>
    <a href="<?= BASE_URL ?>/chamada/sala.php?codigo=<?= urlencode($proximaEmDestaque['sala_codigo']) ?>" class="btn btn-primary">Entrar na chamada</a>
  <?php else: ?>
    <span class="badge badge-<?= $proximaEmDestaque['estado'] ?>" style="font-size:13px;padding:9px 18px;">Aguarda confirmação</span>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <h3><?= $proximaEmDestaque ? 'Outras consultas agendadas' : 'Próximas consultas' ?></h3>
  <table>
    <tr><th>Psicólogo</th><th>Data</th><th>Estado</th><th>Ação</th></tr>
    <?php $listaTabela = $proximaEmDestaque ? $restantes : $proximasConsultas; ?>
    <?php foreach ($listaTabela as $c): ?>
    <tr>
      <td><?= escape($c['nome_psicologo']) ?></td>
      <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
      <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
      <td><?php if ($c['estado'] === 'confirmada'): ?><a href="<?= BASE_URL ?>/chamada/sala.php?codigo=<?= urlencode($c['sala_codigo']) ?>" class="btn btn-small btn-primary">Entrar</a><?php else: ?>-<?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($listaTabela) && !$proximaEmDestaque): ?>
    <tr><td colspan="4" style="text-align:center;padding:30px 0;color:var(--muted);">Ainda não tens consultas agendadas. <a href="<?= BASE_URL ?>/paciente/buscar_psicologos.php" style="color:var(--primary);font-weight:600;">Procurar um psicólogo &rarr;</a></td></tr>
    <?php elseif (empty($listaTabela)): ?>
    <tr><td colspan="4" style="text-align:center;padding:24px 0;color:var(--muted);">Sem mais consultas agendadas de momento.</td></tr>
    <?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
