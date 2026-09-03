<?php
require_once __DIR__ . '/GatewayPagamento.php';
require_once __DIR__ . '/http.php';

/**
 * Gateway RedoPay.
 *
 * AVISO IMPORTANTE: não foi possível confirmar publicamente a
 * documentação oficial da API de comerciante da RedoPay no momento em
 * que este código foi escrito. Esta classe segue o padrão REST mais
 * comum (pedido POST em JSON, autenticação Bearer, confirmação por
 * webhook assinado) para que fique pronta a usar assim que preencheres
 * os dados reais em /admin/metodos_pagamento.php.
 *
 * Antes de ativar em produção:
 * 1. Contacta a RedoPay e obtém a documentação oficial da API.
 * 2. Confirma o endpoint exato, os nomes dos campos do pedido/resposta,
 *    e o nome do cabeçalho usado para a assinatura do webhook.
 * 3. Ajusta os nomes dos campos abaixo (marcados com comentários) para
 *    corresponderem exatamente à documentação da RedoPay.
 */
class RedoPayGateway implements GatewayPagamento {

    private $urlApi;
    private $chavePublica;
    private $chavePrivada;
    private $chaveWebhook;

    public function __construct($config) {
        $this->urlApi = rtrim($config['url_api'], '/');
        $this->chavePublica = $config['chave_publica'];
        $this->chavePrivada = $config['chave_privada'];
        $this->chaveWebhook = $config['chave_webhook'];
    }

    public function iniciarPagamento($dados) {
        if (empty($this->urlApi) || empty($this->chavePrivada)) {
            return ['sucesso' => false, 'estado' => 'erro', 'referencia_gateway' => null,
                    'redirect_url' => null, 'mensagem' => 'RedoPay não está configurado. Contacta o administrador.', 'payload' => []];
        }

        // TODO: confirma os nomes exatos destes campos na documentação oficial da RedoPay
        $corpo = [
            'merchant_key' => $this->chavePublica,
            'reference'    => $dados['referencia'],
            'amount'       => number_format($dados['valor'], 2, '.', ''),
            'currency'     => 'AOA',
            'phone'        => limparTelefone($dados['telefone']),
            'callback_url' => $dados['retorno_url'],
        ];

        $cabecalhos = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->chavePrivada,
        ];

        $resp = pedidoHttpJson($this->urlApi . '/payments', 'POST', $cabecalhos, $corpo);

        if ($resp['erro'] || $resp['codigo'] >= 400 || !$resp['corpo']) {
            return ['sucesso' => false, 'estado' => 'erro', 'referencia_gateway' => null, 'redirect_url' => null,
                    'mensagem' => 'Não foi possível iniciar o pagamento RedoPay: ' . ($resp['erro'] ?: 'resposta inválida da API.'),
                    'payload' => $resp['corpo'] ?: []];
        }

        $refGateway = $resp['corpo']['id'] ?? ($resp['corpo']['transaction_id'] ?? null);
        $redirect = $resp['corpo']['checkout_url'] ?? ($resp['corpo']['payment_url'] ?? null);

        return [
            'sucesso' => true,
            'estado' => 'pendente',
            'referencia_gateway' => $refGateway,
            'redirect_url' => $redirect,
            'mensagem' => $redirect ? 'Vais ser redirecionado para concluir o pagamento com a RedoPay.' : 'Pagamento iniciado, aguarda confirmação.',
            'payload' => $resp['corpo'],
        ];
    }

    public function verificarEstado($referenciaGateway) {
        $cabecalhos = ['Authorization: Bearer ' . $this->chavePrivada];
        $resp = pedidoHttpJson($this->urlApi . '/payments/' . urlencode($referenciaGateway), 'GET', $cabecalhos);

        if ($resp['erro'] || !$resp['corpo']) {
            return ['estado' => 'pendente', 'payload' => []];
        }

        $estadoApi = strtolower($resp['corpo']['status'] ?? 'pending');
        $mapa = ['paid' => 'pago', 'completed' => 'pago', 'failed' => 'falhado', 'cancelled' => 'falhado'];
        $estado = $mapa[$estadoApi] ?? 'pendente';

        return ['estado' => $estado, 'payload' => $resp['corpo']];
    }

    public function validarWebhook($corpoBruto, $assinaturaRecebida) {
        if (empty($this->chaveWebhook)) return false;
        $assinaturaEsperada = hash_hmac('sha256', $corpoBruto, $this->chaveWebhook);
        return hash_equals($assinaturaEsperada, (string)$assinaturaRecebida);
    }
}
