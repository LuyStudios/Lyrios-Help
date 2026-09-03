<?php
require_once __DIR__ . '/../config/config.php';
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>isset($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
session_start();

if (isset($_SESSION['utilizador_id'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/seguranca.php';
    registarLogSeguranca($pdo, 'logout', $_SESSION['utilizador_id'], 'Sessão terminada.');
}

$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
