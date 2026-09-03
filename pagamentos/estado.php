<?php
require_once __DIR__ . '/../includes/funcoes.php';
if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }
require_once __DIR__ . '/../gateways/GatewayFactory.php';

$pagamentoId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.paciente_id FROM pagamentos p JOIN consultas c ON c.id = p.consulta_id WHERE p.id = ?");
$stmt->execute([$pagamentoId]);
$pagamento = $stmt->fetch();

if (!$pagamento || $pagamento['paciente_id'] != $_SESSION['utilizador_id']) {
    die('Pagamento não encontrado.');
}

// Se o utilizador pedir para verificar manualmente o estado
if (isset($_GET['verificar']) && $pagamento['estado'] === 'pendente' && $pagamento['referencia_gateway']) {
    $gateway = GatewayFactory::criar($pdo, $pagamento['gateway']);
    $resultado = $gateway->verificarEstado($pagamento['referencia_gateway']);

    if ($resultado['estado'] === 'pago') {
        $pdo->prepare("UPDATE pagamentos SET estado='pago', payload_resposta=? WHERE id=?")
            ->execute([json_encode($resultado['payload']), $pagamento['id']]);
        // A consulta mantém-se "pendente": aguarda a confirmação de disponibilidade do psicólogo
        registarAtividade($pdo, $_SESSION['utilizador_id'], 'Pagamento confirmado. A aguardar confirmação do psicólogo.');
        header('Location: ' . BASE_URL . '/paciente/minhas_consultas.php?sucesso=1');
        exit;
    } elseif ($resultado['estado'] === 'falhado') {
        $pdo->prepare("UPDATE pagamentos SET estado='falhado' WHERE id=?")->execute([$pagamento['id']]);
    }
    header('Location: ' . BASE_URL . '/pagamentos/estado.php?id=' . $pagamento['id']);
    exit;
}

$titulo = "Estado do Pagamento"; $pagina = 'consultas'; $areaTipo = 'paciente';
require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Estado do Pagamento</h1>
<div class="card" style="max-width:520px;">
  <?php if ($pagamento['estado'] === 'pendente'): ?>
    <div class="alert alert-error">
      O teu pagamento está pendente de confirmação. Se escolheste Multicaixa Express, abre a tua app e confirma a operação com o teu PIN.
    </div>
    <a href="?id=<?= $pagamento['id'] ?>&verificar=1" class="btn btn-primary btn-full">Já confirmei — Verificar estado</a>
  <?php elseif ($pagamento['estado'] === 'pago'): ?>
    <div class="alert alert-success">Pagamento confirmado com sucesso!</div>
    <a href="<?= BASE_URL ?>/paciente/minhas_consultas.php" class="btn btn-primary btn-full">Ver minhas consultas</a>
  <?php else: ?>
    <div class="alert alert-error">O pagamento falhou ou foi cancelado. Tenta novamente.</div>
    <a href="<?= BASE_URL ?>/paciente/marcar_consulta.php" class="btn btn-primary btn-full">Tentar novamente</a>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
