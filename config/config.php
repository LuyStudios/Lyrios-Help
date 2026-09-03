<?php
/**
 * Configuração base do site.
 *
 * O BASE_URL é calculado automaticamente consoante a pasta onde colocares
 * o projeto (ex: http://localhost/mindcare ou http://localhost se
 * colocares os ficheiros diretamente na raiz do servidor).
 *
 * Se preferires, podes forçar um valor fixo substituindo a linha
 * "define('BASE_URL', ...)" por algo como: define('BASE_URL', '/mindcare');
 */
if (!defined('BASE_URL')) {
    // Pasta onde está este ficheiro (…/mindcare/config), subimos um nível para a raiz do projeto
    $pastaProjeto = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));

    $baseUrl = '';
    // stripos evita problemas de maiúsculas/minúsculas comuns no Windows (XAMPP)
    if (stripos($pastaProjeto, $documentRoot) === 0) {
        $baseUrl = substr($pastaProjeto, strlen($documentRoot));
    }
    define('BASE_URL', rtrim($baseUrl, '/'));
}
