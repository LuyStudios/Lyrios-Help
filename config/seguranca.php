<?php
/**
 * SEGURANÇA CENTRAL DA PLATAFORMA
 * - Proteção CSRF em formulários
 * - Bloqueio por tentativas de login falhadas (força bruta)
 * - Cabeçalhos de segurança HTTP
 * - Registo de eventos de segurança
 */

/** Envia cabeçalhos de segurança HTTP em todas as páginas */
function enviarCabecalhosSeguranca() {
    if (headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    // CSP: permite os recursos externos usados pela plataforma (fontes, ícones, videochamada Jitsi)
    header("Content-Security-Policy: default-src 'self'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
        "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
        "script-src 'self' 'unsafe-inline' https://meet.jit.si; " .
        "img-src 'self' data: https:; " .
        "frame-src https://meet.jit.si; " .
        "media-src 'self' blob:; " .
        "connect-src 'self' https://meet.jit.si wss://meet.jit.si;");
}

/* ============================================================
 * PROTEÇÃO CSRF
 * ============================================================ */

/** Gera (ou reutiliza) o token CSRF da sessão atual */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Imprime o campo escondido com o token CSRF, para colocar dentro de um <form> */
function csrfCampo() {
    echo '<input type="hidden" name="csrf_token" value="' . escape(csrfToken()) . '">';
}

/**
 * Verifica o token CSRF de um pedido POST.
 * Se inválido, termina a execução com erro 403.
 */
function csrfVerificar() {
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenEnviado)) {
        http_response_code(403);
        die('Pedido inválido ou expirado (proteção CSRF). Volta atrás e tenta novamente.');
    }
}

/* ============================================================
 * PROTEÇÃO CONTRA FORÇA BRUTA NO LOGIN
 * ============================================================ */

define('MAX_TENTATIVAS_LOGIN', 5);
define('MINUTOS_BLOQUEIO_LOGIN', 15);

/**
 * Verifica se a conta associada ao email está temporariamente bloqueada
 * devido a demasiadas tentativas de login falhadas.
 * Devolve o número de minutos restantes de bloqueio, ou 0 se não estiver bloqueada.
 */
function verificarBloqueioLogin($pdo, $email) {
    $stmt = $pdo->prepare("SELECT bloqueado_ate FROM utilizadores WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && $row['bloqueado_ate'] && strtotime($row['bloqueado_ate']) > time()) {
        return (int) ceil((strtotime($row['bloqueado_ate']) - time()) / 60);
    }
    return 0;
}

/** Regista uma tentativa de login falhada e bloqueia a conta se exceder o limite */
function registarTentativaFalhada($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id, tentativas_login FROM utilizadores WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) return;

    $tentativas = (int)$user['tentativas_login'] + 1;
    if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
        $bloqueadoAte = date('Y-m-d H:i:s', time() + MINUTOS_BLOQUEIO_LOGIN * 60);
        $pdo->prepare("UPDATE utilizadores SET tentativas_login = ?, bloqueado_ate = ? WHERE id = ?")
            ->execute([$tentativas, $bloqueadoAte, $user['id']]);
        registarLogSeguranca($pdo, 'login_bloqueado', $user['id'], 'Conta bloqueada temporariamente por excesso de tentativas de login.');
    } else {
        $pdo->prepare("UPDATE utilizadores SET tentativas_login = ? WHERE id = ?")
            ->execute([$tentativas, $user['id']]);
    }
}

/** Limpa o contador de tentativas falhadas após um login bem-sucedido */
function limparTentativasLogin($pdo, $userId) {
    $pdo->prepare("UPDATE utilizadores SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?")
        ->execute([$userId]);
}

/* ============================================================
 * REGISTO DE EVENTOS DE SEGURANÇA
 * ============================================================ */

function registarLogSeguranca($pdo, $tipo, $utilizador_id, $descricao) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $stmt = $pdo->prepare("INSERT INTO logs_seguranca (tipo, utilizador_id, ip, descricao) VALUES (?,?,?,?)");
    $stmt->execute([$tipo, $utilizador_id, $ip, $descricao]);
}

/* ============================================================
 * SANITIZAÇÃO DE ENTRADAS
 * ============================================================ */

/** Remove espaços e valida um número de telefone angolano simples (9 dígitos) */
function limparTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return $telefone;
}
