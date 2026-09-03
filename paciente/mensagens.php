<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Mensagens"; $pagina = 'mensagens'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nome, u.foto
    FROM consultas c JOIN utilizadores u ON u.id = c.psicologo_id
    WHERE c.paciente_id = ?
    ORDER BY u.nome ASC
");
$stmt->execute([$uid]);
$psicologos = $stmt->fetchAll();

foreach ($psicologos as &$p) {
    $stmtC = $pdo->prepare("SELECT id FROM conversas WHERE paciente_id=? AND psicologo_id=?");
    $stmtC->execute([$uid, $p['id']]);
    $conv = $stmtC->fetch();
    $p['ultima_mensagem'] = null; $p['nao_lidas'] = 0;
    if ($conv) {
        $stmtM = $pdo->prepare("SELECT tipo, conteudo FROM mensagens_chat WHERE conversa_id=? ORDER BY id DESC LIMIT 1");
        $stmtM->execute([$conv['id']]);
        $ultima = $stmtM->fetch();
        if ($ultima) $p['ultima_mensagem'] = $ultima['tipo'] === 'audio' ? '🎤 Mensagem de áudio' : ($ultima['tipo'] === 'chamada' ? '📹 Chamada de vídeo' : $ultima['conteudo']);

        $stmtN = $pdo->prepare("SELECT COUNT(*) c FROM mensagens_chat WHERE conversa_id=? AND remetente_id != ? AND lida=0");
        $stmtN->execute([$conv['id'], $uid]);
        $p['nao_lidas'] = $stmtN->fetch()['c'];
    }
}
unset($p);

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Mensagens</h1>
<?php if (empty($psicologos)): ?>
  <div class="card"><p>Ainda não tens conversas. Assim que marcares uma consulta, podes trocar mensagens com o teu psicólogo aqui.</p></div>
<?php else: ?>
<div class="chat-lista">
  <?php foreach ($psicologos as $p): ?>
  <a href="<?= BASE_URL ?>/paciente/chat.php?id=<?= $p['id'] ?>" class="chat-lista-item">
    <?php if ($p['foto']): ?>
      <img src="<?= BASE_URL ?>/<?= escape($p['foto']) ?>" class="chat-avatar">
    <?php else: ?>
      <div class="chat-avatar-placeholder"><i class="fa-solid fa-user"></i></div>
    <?php endif; ?>
    <div class="chat-lista-info">
      <div class="chat-lista-nome"><?= escape($p['nome']) ?></div>
      <div class="chat-lista-preview"><?= $p['ultima_mensagem'] ? escape($p['ultima_mensagem']) : 'Ainda sem mensagens' ?></div>
    </div>
    <?php if ($p['nao_lidas'] > 0): ?><span class="chat-badge-nao-lida"><?= $p['nao_lidas'] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
