<?php
require_once __DIR__ . '/GatewayPagamento.php';
require_once __DIR__ . '/http.php';

/**
 * Gateway Multicaixa Express (GPO da EMIS, normalmente acedido através
 * de um provedor certificado como o vPOS - developer.vpos.ao).
 *
 * O fluxo real é: o cliente introduz o número de telemóvel associado à
 * conta Multicaixa Express, a plataforma pede o pagamento à API, e o
 * cliente recebe uma notificação na app Multicaixa Express para
 * confirmar o pagamento com o PIN. É por isso um pagamento por "push",
 * sem redirecionamento — o estado fica "pendente" até o cliente confirmar
 * no telemóvel dele (confirmação chega via webhook ou é verificada por polling).
 *
 * IMPORTANTE: preenche os campos url_api, pos_id, supervisor_card e
 * chave_privada (token) em /admin/metodos_pagamento.php com os dados
 * que a EMIS ou o teu provedor (ex: vPOS) te fornecerem. O URL exato
 * da API deve ser confirmado na tua conta/dashboard do provedor, pois
 * a EMIS não publica um único endpoint universal.
 */
class MulticaixaExpressGateway implements GatewayPagamento {

    private $urlApi;
    private $token;
    private $posId;
    private $supervisorCard;
    private $chaveWebhook;

    public function __construct($config) {
        $this->urlApi = rtrim($config['url_api'], '/');
        $this->token = $config['chave_privada'];
        $this->posId = $config['pos_id'];
        $this->supervisorCard = $config['supervisor_card'];
        $this->chaveWebhook = $config['chave_webhook'];
    }

    public function iniciarPagamento($dados) {
        if (empty($this->urlApi) || empty($this->token)) {
            return ['sucesso' => false, 'estado' => 'erro', 'referencia_gateway' => null,
                    'redirect_url' => null, 'mensagem' => 'Multicaixa Express não está configurado. Contacta o administrador.', 'payload' => []];
        }

        $corpo = [
            'pos_id'          => $this->posId,
            'supervisor_card' => $this->supervisorCard,
            'mobile'          => limparTelefone($dados['telefone']),
            'amount'          => number_format($dados['valor'], 2, '.', ''),
            'reference'       => $dados['referencia'],
            'callback_url'    => $dados['retorno_url'],
        ];

        $cabecalhos = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token,
        ];

        $resp = pedidoHttpJson($this->urlApi . '/transactions', 'POST', $cabecalhos, $corpo);

        if ($resp['erro'] || $resp['codigo'] >= 400 || !$resp['corpo']) {
            return ['sucesso' => false, 'estado' => 'erro', 'referencia_gateway' => null, 'redirect_url' => null,
                    'mensagem' => 'Não foi possível iniciar o pagamento Multicaixa Express: ' . ($resp['erro'] ?: 'resposta inválida da API.'),
                    'payload' => $resp['corpo'] ?: []];
        }

        $refGateway = isset($resp['corpo']['id']) ? $resp['corpo']['id'] : null;

        return [
            'sucesso' => true,
            'estado' => 'pendente', // fica pendente até o cliente confirmar na app
            'referencia_gateway' => $refGateway,
            'redirect_url' => null, // não há redirecionamento, é confirmação por push na app
            'mensagem' => 'Pedido de pagamento enviado. Confirma no teu telemóvel através da app Multicaixa Express.',
            'payload' => $resp['corpo'],
        ];
    }

    public function verificarEstado($referenciaGateway) {
        $cabecalhos = ['Authorization: Bearer ' . $this->token];
        $resp = pedidoHttpJson($this->urlApi . '/transactions/' . urlencode($referenciaGateway), 'GET', $cabecalhos);

        if ($resp['erro'] || !$resp['corpo']) {
            return ['estado' => 'pendente', 'payload' => []];
        }

        $estadoApi = strtolower($resp['corpo']['status'] ?? 'pending');
        $mapa = ['accepted' => 'pago', 'success' => 'pago', 'rejected' => 'falhado', 'failed' => 'falhado'];
        $estado = $mapa[$estadoApi] ?? 'pendente';

        return ['estado' => $estado, 'payload' => $resp['corpo']];
    }

    public function validarWebhook($corpoBruto, $assinaturaRecebida) {
        if (empty($this->chaveWebhook)) return false;
        $assinaturaEsperada = hash_hmac('sha256', $corpoBruto, $this->chaveWebhook);
        return hash_equals($assinaturaEsperada, (string)$assinaturaRecebida);
    }
}
