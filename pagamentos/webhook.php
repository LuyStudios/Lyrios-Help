<?php
/**
 * Endpoint de webhook: recebe as notificações de pagamento enviadas
 * pelos gateways (Multicaixa Express, RedoPay, Wesi).
 * URL a configurar em cada gateway: /pagamentos/webhook.php?gateway=multicaixa (ou redopay / wesi)
 *
 * Segurança: a notificação só é aceite se a assinatura enviada pelo
 * gateway (calculada com a chave secreta de webhook configurada no
 * admin) corresponder à assinatura calculada aqui. Isto impede que
 * alguém finja um pagamento chamando este URL diretamente.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/seguranca.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../gateways/GatewayFactory.php';

header('Content-Type: application/json');

$gatewayNome = $_GET['gateway'] ?? '';
$corpoBruto = file_get_contents('php://input');
$assinatura = $_SERVER['HTTP_X_SIGNATURE'] ?? ($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '');

if (!in_array($gatewayNome, ['multicaixa', 'redopay', 'wesi'], true)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Gateway inválido.']);
    exit;
}

$gateway = GatewayFactory::criar($pdo, $gatewayNome);

if (!$gateway->validarWebhook($corpoBruto, $assinatura)) {
    registarLogSeguranca($pdo, 'webhook_invalido', null, "Assinatura de webhook inválida recebida para o gateway: $gatewayNome");
    http_response_code(401);
    echo json_encode(['erro' => 'Assinatura inválida.']);
    exit;
}

$dados = json_decode($corpoBruto, true);
if (!$dados) {
    http_response_code(400);
    echo json_encode(['erro' => 'Corpo do pedido inválido.']);
    exit;
}

// Tentamos encontrar a referência do nosso lado (guardada como referencia_gateway)
$referenciaGateway = $dados['id'] ?? ($dados['transaction_id'] ?? ($dados['charge_id'] ?? null));
$estadoRecebido = strtolower($dados['status'] ?? '');

if (!$referenciaGateway) {
    http_response_code(400);
    echo json_encode(['erro' => 'Referência de transação em falta.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE referencia_gateway = ? AND gateway = ?");
$stmt->execute([$referenciaGateway, $gatewayNome]);
$pagamento = $stmt->fetch();

if (!$pagamento) {
    http_response_code(404);
    echo json_encode(['erro' => 'Pagamento não encontrado.']);
    exit;
}

$mapaPago = ['accepted', 'success', 'paid', 'completed'];
$mapaFalhado = ['rejected', 'failed', 'cancelled'];

if (in_array($estadoRecebido, $mapaPago, true)) {
    $pdo->prepare("UPDATE pagamentos SET estado = 'pago', payload_resposta = ? WHERE id = ?")
        ->execute([json_encode($dados), $pagamento['id']]);
    // A consulta mantém-se "pendente": aguarda a confirmação de disponibilidade do psicólogo
    registarLogSeguranca($pdo, 'pagamento_confirmado', null, "Pagamento #{$pagamento['id']} confirmado via webhook $gatewayNome.");
} elseif (in_array($estadoRecebido, $mapaFalhado, true)) {
    $pdo->prepare("UPDATE pagamentos SET estado = 'falhado', payload_resposta = ? WHERE id = ?")
        ->execute([json_encode($dados), $pagamento['id']]);
    registarLogSeguranca($pdo, 'pagamento_falhado', null, "Pagamento #{$pagamento['id']} falhou via webhook $gatewayNome.");
}

echo json_encode(['sucesso' => true]);
