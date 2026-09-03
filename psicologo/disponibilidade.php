<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('psicologo');
$titulo = "Disponibilidade"; $pagina = 'disponibilidade'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];
$sucesso = '';

$dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    if (isset($_POST['adicionar'])) {
        $diaSemana = validarInteiro($_POST['dia_semana'] ?? -1, 0, 6, -1);
        $horaInicio = preg_match('/^\d{2}:\d{2}$/', $_POST['hora_inicio'] ?? '') ? $_POST['hora_inicio'] : false;
        $horaFim = preg_match('/^\d{2}:\d{2}$/', $_POST['hora_fim'] ?? '') ? $_POST['hora_fim'] : false;

        if ($diaSemana < 0 || !$horaInicio || !$horaFim || $horaFim <= $horaInicio) {
            $sucesso = '';
            $erro = 'Preenche um dia e um intervalo de horas válido (hora final depois da inicial).';
        } else {
            $stmt = $pdo->prepare("INSERT INTO disponibilidades (psicologo_id, dia_semana, hora_inicio, hora_fim) VALUES (?,?,?,?)");
            $stmt->execute([$uid, $diaSemana, $horaInicio, $horaFim]);
            $sucesso = 'Disponibilidade adicionada com sucesso.';
        }
    } elseif (isset($_POST['remover'])) {
        $pdo->prepare("DELETE FROM disponibilidades WHERE id = ? AND psicologo_id = ?")->execute([(int)$_POST['id'], $uid]);
        $sucesso = 'Disponibilidade removida.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM disponibilidades WHERE psicologo_id = ? ORDER BY dia_semana ASC, hora_inicio ASC");
$stmt->execute([$uid]);
$disponibilidades = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Disponibilidade Semanal</h1>
<p style="color:#697871;">Define os dias e horários em que estás disponível. Os pacientes só vão poder marcar consultas dentro destes intervalos.</p>
<?php if (!empty($erro)): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:22px;max-width:600px;">
  <h3>Adicionar horário</h3>
  <form method="post" class="grid grid-3" style="align-items:end;">
    <?php csrfCampo(); ?>
    <div class="form-group">
      <label>Dia da semana</label>
      <select name="dia_semana" required>
        <?php foreach ($dias as $i => $nomeDia): ?><option value="<?= $i ?>"><?= $nomeDia ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Das</label><input type="time" name="hora_inicio" required></div>
    <div class="form-group"><label>Até</label><input type="time" name="hora_fim" required></div>
    <div class="form-group" style="grid-column:1/-1;"><button class="btn btn-primary" name="adicionar" type="submit">Adicionar</button></div>
  </form>
</div>

<div class="card" style="max-width:600px;">
  <table>
    <tr><th>Dia</th><th>Das</th><th>Até</th><th></th></tr>
    <?php foreach ($disponibilidades as $d): ?>
    <tr>
      <td><?= $dias[$d['dia_semana']] ?></td>
      <td><?= substr($d['hora_inicio'], 0, 5) ?></td>
      <td><?= substr($d['hora_fim'], 0, 5) ?></td>
      <td><form method="post"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><button name="remover" class="btn btn-small btn-danger">Remover</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($disponibilidades)): ?><tr><td colspan="4">Ainda não definiste nenhuma disponibilidade. Sem isso definido, os pacientes podem marcar em qualquer horário.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
