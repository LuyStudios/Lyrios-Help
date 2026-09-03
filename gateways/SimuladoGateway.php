<?php
require_once __DIR__ . '/GatewayPagamento.php';

/**
 * Gateway simulado: confirma o pagamento de imediato, sem contactar
 * nenhuma API externa. Útil para testar a plataforma antes de teres
 * credenciais reais da Multicaixa Express, RedoPay ou Wesi.
 * NÃO usar em produção.
 */
class SimuladoGateway implements GatewayPagamento {

    public function __construct($config) {}

    public function iniciarPagamento($dados) {
        return [
            'sucesso' => true,
            'estado' => 'pago',
            'referencia_gateway' => 'SIM-' . strtoupper(bin2hex(random_bytes(6))),
            'redirect_url' => null,
            'mensagem' => 'Pagamento simulado confirmado com sucesso.',
            'payload' => ['modo' => 'simulado'],
        ];
    }

    public function verificarEstado($referenciaGateway) {
        return ['estado' => 'pago', 'payload' => ['modo' => 'simulado']];
    }

    public function validarWebhook($corpoBruto, $assinaturaRecebida) {
        return true;
    }
}
