<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Questionário Inicial"; $pagina = 'questionario'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $respostas = $_POST['resposta'] ?? [];
    foreach ($respostas as $perguntaId => $resposta) {
        $perguntaId = (int)$perguntaId;
        $resposta = trim($resposta);
        if ($resposta === '') continue;
        $stmt = $pdo->prepare("
            INSERT INTO respostas_questionario (paciente_id, pergunta_id, resposta) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE resposta = VALUES(resposta)
        ");
        $stmt->execute([$uid, $perguntaId, mb_substr($resposta, 0, 1000)]);
    }
    registarAtividade($pdo, $uid, 'Preencheu/atualizou o questionário inicial.');
    $sucesso = 'Respostas guardadas com sucesso. Obrigado por partilhares!';
}

$perguntas = $pdo->query("SELECT * FROM perguntas_questionario WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();

$stmt = $pdo->prepare("SELECT pergunta_id, resposta FROM respostas_questionario WHERE paciente_id = ?");
$stmt->execute([$uid]);
$respostasExistentes = [];
foreach ($stmt->fetchAll() as $r) { $respostasExistentes[$r['pergunta_id']] = $r['resposta']; }

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Questionário Inicial</h1>
<p style="color:#697871;">Estas respostas ajudam o teu psicólogo a preparar melhor a primeira consulta. Podes editar as respostas sempre que quiseres.</p>
<?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
<div class="card" style="max-width:640px;">
  <form method="post">
    <?php csrfCampo(); ?>
    <?php foreach ($perguntas as $p): ?>
    <div class="form-group">
      <label><?= escape($p['texto']) ?></label>
      <textarea name="resposta[<?= $p['id'] ?>]"><?= escape($respostasExistentes[$p['id']] ?? '') ?></textarea>
    </div>
    <?php endforeach; ?>
    <?php if (empty($perguntas)): ?><p>Ainda não existem perguntas configuradas.</p><?php else: ?>
    <button class="btn btn-primary btn-full" type="submit">Guardar respostas</button>
    <?php endif; ?>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
