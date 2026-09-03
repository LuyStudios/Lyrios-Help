<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Minhas Atividades"; $pagina = 'atividades'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("SELECT * FROM atividades WHERE utilizador_id = ? ORDER BY criado_em DESC LIMIT 50");
$stmt->execute([$uid]);
$atividades = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Minhas Atividades</h1>
<div class="card">
  <table>
    <tr><th>Atividade</th><th>Data</th></tr>
    <?php foreach ($atividades as $a): ?>
    <tr><td><?= escape($a['descricao']) ?></td><td><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($atividades)): ?><tr><td colspan="2">Sem atividades registadas.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
