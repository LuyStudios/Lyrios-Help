<?php
require_once __DIR__ . '/SimuladoGateway.php';
require_once __DIR__ . '/MulticaixaExpressGateway.php';
require_once __DIR__ . '/RedoPayGateway.php';
require_once __DIR__ . '/WesiGateway.php';

class GatewayFactory {

    /**
     * Cria a instância do gateway pedido, usando a configuração
     * guardada na tabela metodos_pagamento.
     */
    public static function criar($pdo, $gateway) {
        $stmt = $pdo->prepare("SELECT * FROM metodos_pagamento WHERE gateway = ?");
        $stmt->execute([$gateway]);
        $config = $stmt->fetch();

        if (!$config) {
            $config = ['url_api' => '', 'chave_publica' => '', 'chave_privada' => '', 'pos_id' => '', 'supervisor_card' => '', 'chave_webhook' => ''];
        }

        switch ($gateway) {
            case 'multicaixa':
                return new MulticaixaExpressGateway($config);
            case 'redopay':
                return new RedoPayGateway($config);
            case 'wesi':
                return new WesiGateway($config);
            case 'simulado':
            default:
                return new SimuladoGateway($config);
        }
    }

    /** Devolve a lista de métodos de pagamento ativos, para mostrar ao paciente */
    public static function metodosAtivos($pdo) {
        return $pdo->query("SELECT * FROM metodos_pagamento WHERE ativo = 1 ORDER BY FIELD(gateway,'multicaixa','redopay','wesi','simulado')")->fetchAll();
    }
}
