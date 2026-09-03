<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Remarcar Consulta"; $pagina = 'consultas'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$consultaId = validarInteiro($_GET['consulta_id'] ?? ($_POST['consulta_id'] ?? 0), 1, null, 0);

$stmt = $pdo->prepare("SELECT c.*, u.nome AS nome_psicologo FROM consultas c JOIN utilizadores u ON u.id = c.psicologo_id WHERE c.id = ? AND c.paciente_id = ?");
$stmt->execute([$consultaId, $uid]);
$consulta = $stmt->fetch();

if (!$consulta) { die('Consulta não encontrada.'); }
if (!in_array($consulta['estado'], ['pendente', 'confirmada'], true)) { die('Esta consulta já não pode ser remarcada.'); }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $data = validarData($_POST['data'] ?? '');
    $hora = preg_match('/^\d{2}:\d{2}$/', $_POST['hora'] ?? '') ? $_POST['hora'] : false;

    if (!$data || !$hora) {
        $erro = 'Indica uma data e hora válidas.';
    } else {
        $novaDataHora = $data . ' ' . $hora . ':00';
        if (strtotime($novaDataHora) < time()) {
            $erro = 'Escolhe uma data e hora futura.';
        } else {
            $disponibilidade = verificarDisponibilidadePsicologo($pdo, $consulta['psicologo_id'], $novaDataHora, $consultaId);
            if ($disponibilidade !== true) {
                $erro = $disponibilidade;
            } else {
                $pdo->prepare("UPDATE consultas SET data_hora = ? WHERE id = ?")->execute([$novaDataHora, $consultaId]);
                registarAtividade($pdo, $uid, 'Remarcou a consulta com ' . $consulta['nome_psicologo'] . '.');
                registarAtividade($pdo, $consulta['psicologo_id'], 'Um paciente remarcou uma consulta para ' . date('d/m/Y H:i', strtotime($novaDataHora)) . '.');
                header('Location: ' . BASE_URL . '/paciente/minhas_consultas.php?remarcada=1');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Remarcar Consulta</h1>
<div class="card" style="max-width:480px;">
  <p>Consulta atual com <strong><?= escape($consulta['nome_psicologo']) ?></strong>: <?= date('d/m/Y H:i', strtotime($consulta['data_hora'])) ?></p>
  <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <input type="hidden" name="consulta_id" value="<?= $consultaId ?>">
    <div class="form-group"><label>Nova data</label><input type="date" name="data" min="<?= date('Y-m-d') ?>" required></div>
    <div class="form-group"><label>Nova hora</label><input type="time" name="hora" required></div>
    <button class="btn btn-primary btn-full" type="submit">Confirmar remarcação</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
