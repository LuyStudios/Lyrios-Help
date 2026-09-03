<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/seguranca.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/avaliacoes.php';

// Configuração reforçada dos cookies de sessão (antes de iniciar a sessão)
if (session_status() === PHP_SESSION_NONE) {
    $seguro = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $seguro,   // só envia o cookie por HTTPS quando disponível
        'httponly' => true,    // impede acesso ao cookie via JavaScript
        'samesite' => 'Lax',   // mitiga ataques CSRF vindos de outros sites
    ]);
    session_start();
}

enviarCabecalhosSeguranca();

/** Verifica se o utilizador está autenticado */
function estaLogado() {
    return isset($_SESSION['utilizador_id']);
}

/** Exige um determinado tipo de utilizador (paciente, psicologo, admin) */
function exigirTipo($tipo) {
    if (!estaLogado() || $_SESSION['tipo'] !== $tipo) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/** Regista uma atividade no histórico do utilizador */
function registarAtividade($pdo, $utilizador_id, $descricao) {
    $stmt = $pdo->prepare("INSERT INTO atividades (utilizador_id, descricao) VALUES (?, ?)");
    $stmt->execute([$utilizador_id, $descricao]);
}

/** Devolve a percentagem de comissão atual da plataforma */
function percentagemComissao($pdo) {
    $stmt = $pdo->query("SELECT percentagem_comissao FROM configuracoes LIMIT 1");
    $row = $stmt->fetch();
    return $row ? (float)$row['percentagem_comissao'] : 20.00;
}

/** Gera um código único para a sala de videochamada */
function gerarCodigoSala() {
    return 'mindcare-' . bin2hex(random_bytes(8));
}

/**
 * Verifica se um psicólogo pode aceitar uma nova consulta numa data/hora:
 * - dentro da disponibilidade semanal definida pelo próprio psicólogo (se existir)
 * - máximo de 10 consultas por dia por profissional
 * - mínimo de 45 minutos entre consultas do mesmo profissional
 * Devolve true se estiver disponível, ou uma string com o motivo se não estiver.
 */
function verificarDisponibilidadePsicologo($pdo, $psicologoId, $dataHora, $ignorarConsultaId = null) {
    $dia = date('Y-m-d', strtotime($dataHora));
    $diaSemana = (int)date('w', strtotime($dataHora));
    $hora = date('H:i:s', strtotime($dataHora));

    // Só aplica a restrição de disponibilidade semanal se o psicólogo tiver definido alguma
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM disponibilidades WHERE psicologo_id = ?");
    $stmt->execute([$psicologoId]);
    if ((int)$stmt->fetch()['c'] > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM disponibilidades WHERE psicologo_id = ? AND dia_semana = ? AND ? BETWEEN hora_inicio AND hora_fim");
        $stmt->execute([$psicologoId, $diaSemana, $hora]);
        if ((int)$stmt->fetch()['c'] === 0) {
            return 'Este psicólogo não está disponível nesse dia/hora. Consulta os horários disponíveis no perfil dele.';
        }
    }

    $sql = "SELECT COUNT(*) c FROM consultas WHERE psicologo_id = ? AND DATE(data_hora) = ? AND estado != 'cancelada'";
    $params = [$psicologoId, $dia];
    if ($ignorarConsultaId) { $sql .= " AND id != ?"; $params[] = $ignorarConsultaId; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ((int)$stmt->fetch()['c'] >= 10) {
        return 'Este psicólogo já atingiu o limite de 10 consultas nesse dia. Escolhe outro dia.';
    }

    $sql = "SELECT data_hora FROM consultas WHERE psicologo_id = ? AND estado != 'cancelada'";
    $params = [$psicologoId];
    if ($ignorarConsultaId) { $sql .= " AND id != ?"; $params[] = $ignorarConsultaId; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existentes = $stmt->fetchAll();

    $novoTimestamp = strtotime($dataHora);
    foreach ($existentes as $c) {
        $diffMinutos = abs($novoTimestamp - strtotime($c['data_hora'])) / 60;
        if ($diffMinutos < 45) {
            return 'Este horário está demasiado próximo de outra consulta do psicólogo (mínimo de 45 minutos entre sessões).';
        }
    }

    return true;
}

/** Formata valores monetários em Kwanzas */
function formatarKz($valor) {
    return number_format($valor, 2, ',', '.') . ' Kz';
}

/** Resolve o caminho de uma foto, aceitando tanto um caminho local (uploads/...) como um URL completo (https://...) */
function urlFoto($caminho) {
    if (!$caminho) return null;
    return (strpos($caminho, 'http://') === 0 || strpos($caminho, 'https://') === 0) ? $caminho : (BASE_URL . '/' . $caminho);
}

function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
