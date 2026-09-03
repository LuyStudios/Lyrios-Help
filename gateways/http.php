<?php
/**
 * Executa um pedido HTTP (POST ou GET) em JSON usando cURL.
 * Usado por todos os gateways de pagamento para comunicar com as APIs externas.
 *
 * @return array ['codigo'=>int, 'corpo'=>array|null, 'erro'=>string|null]
 */
function pedidoHttpJson($url, $metodo, $cabecalhos, $corpoArray = null) {
    if (!function_exists('curl_init')) {
        return ['codigo' => 0, 'corpo' => null, 'erro' => 'A extensão cURL do PHP não está ativa neste servidor.'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($metodo));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cabecalhos);

    if ($corpoArray !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpoArray));
    }

    $resposta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($erroCurl) {
        return ['codigo' => 0, 'corpo' => null, 'erro' => $erroCurl];
    }

    $corpo = json_decode($resposta, true);
    return ['codigo' => $codigo, 'corpo' => $corpo, 'erro' => null];
}
