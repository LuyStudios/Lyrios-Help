<?php
/**
 * Interface que todos os gateways de pagamento devem implementar.
 * Isto permite adicionar ou trocar gateways sem alterar o resto do código.
 */
interface GatewayPagamento {

    /**
     * Inicia um pagamento.
     * @param array $dados ['referencia'=>string, 'valor'=>float, 'telefone'=>string, 'retorno_url'=>string]
     * @return array ['sucesso'=>bool, 'estado'=>'pendente'|'pago'|'erro', 'referencia_gateway'=>string|null,
     *                'redirect_url'=>string|null, 'mensagem'=>string, 'payload'=>array]
     */
    public function iniciarPagamento($dados);

    /**
     * Consulta o estado atual de uma transação já iniciada.
     * @param string $referenciaGateway
     * @return array ['estado'=>'pendente'|'pago'|'falhado', 'payload'=>array]
     */
    public function verificarEstado($referenciaGateway);

    /**
     * Valida a assinatura de um webhook recebido do gateway,
     * para garantir que a notificação é mesmo autêntica.
     * @param string $corpoBruto Corpo bruto (raw) do pedido recebido
     * @param string $assinaturaRecebida Valor do cabeçalho de assinatura enviado pelo gateway
     * @return bool
     */
    public function validarWebhook($corpoBruto, $assinaturaRecebida);
}
