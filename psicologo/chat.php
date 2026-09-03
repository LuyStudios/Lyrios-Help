<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../chat/util.php';
exigirTipo('psicologo');
$titulo = "Chat"; $pagina = 'mensagens'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];

$pacienteId = (int)($_GET['id'] ?? 0);

if (!existeRelacaoConsulta($pdo, $pacienteId, $uid)) {
    die('Só podes conversar com pacientes que já tenham marcado consulta contigo.');
}

$stmt = $pdo->prepare("SELECT nome, foto FROM utilizadores WHERE id = ? AND tipo='paciente'");
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();
if (!$paciente) { die('Paciente não encontrado.'); }

$conversaId = obterOuCriarConversa($pdo, $pacienteId, $uid);

$stmt = $pdo->prepare("SELECT * FROM mensagens_chat WHERE conversa_id = ? ORDER BY id ASC LIMIT 100");
$stmt->execute([$conversaId]);
$mensagensIniciais = $stmt->fetchAll();

$pdo->prepare("UPDATE mensagens_chat SET lida = 1 WHERE conversa_id = ? AND remetente_id != ? AND lida = 0")->execute([$conversaId, $uid]);

require_once __DIR__ . '/../includes/dash_header.php';

$outroNome = $paciente['nome'];
$outroFoto = $paciente['foto'];
$voltarUrl = BASE_URL . '/psicologo/mensagens.php';
?>
<h1>Conversa</h1>
<?php require __DIR__ . '/../includes/chat_ui.php'; ?>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
