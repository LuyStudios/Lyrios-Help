<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../chat/util.php';
exigirTipo('paciente');
$titulo = "Chat"; $pagina = 'mensagens'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$psicologoId = (int)($_GET['id'] ?? 0);

if (!existeRelacaoConsulta($pdo, $uid, $psicologoId)) {
    die('Só podes conversar com psicólogos com quem já tenhas marcado consulta.');
}

$stmt = $pdo->prepare("SELECT nome, foto FROM utilizadores WHERE id = ? AND tipo='psicologo'");
$stmt->execute([$psicologoId]);
$psicologo = $stmt->fetch();
if (!$psicologo) { die('Psicólogo não encontrado.'); }

$conversaId = obterOuCriarConversa($pdo, $uid, $psicologoId);

$stmt = $pdo->prepare("SELECT * FROM mensagens_chat WHERE conversa_id = ? ORDER BY id ASC LIMIT 100");
$stmt->execute([$conversaId]);
$mensagensIniciais = $stmt->fetchAll();

$pdo->prepare("UPDATE mensagens_chat SET lida = 1 WHERE conversa_id = ? AND remetente_id != ? AND lida = 0")->execute([$conversaId, $uid]);

require_once __DIR__ . '/../includes/dash_header.php';

$outroNome = $psicologo['nome'];
$outroFoto = $psicologo['foto'];
$voltarUrl = BASE_URL . '/paciente/mensagens.php';
?>
<h1>Conversa</h1>
<?php require __DIR__ . '/../includes/chat_ui.php'; ?>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
