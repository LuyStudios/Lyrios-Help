<?php
/**
 * EXECUTA ESTE FICHEIRO UMA ÚNICA VEZ NO NAVEGADOR PARA CRIAR/REDEFINIR O ADMIN.
 * Depois de usares, apaga este ficheiro do servidor por segurança.
 * Acesso: http://teusite.com/criar_admin.php
 */
require_once __DIR__ . '/config/database.php';

$emailAdmin = 'admin@lyrios.com';
$passwordAdmin = 'admin123'; // muda esta password depois do primeiro login
$hash = password_hash($passwordAdmin, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ?");
$stmt->execute([$emailAdmin]);
$existe = $stmt->fetch();

if ($existe) {
    $pdo->prepare("UPDATE utilizadores SET password = ? WHERE email = ?")->execute([$hash, $emailAdmin]);
    echo "Password do admin redefinida com sucesso.<br>";
} else {
    $pdo->prepare("INSERT INTO utilizadores (nome, email, password, tipo, estado) VALUES (?,?,?, 'admin', 'ativo')")
        ->execute(['Administrador', $emailAdmin, $hash]);
    echo "Conta admin criada com sucesso.<br>";
}

echo "Email: $emailAdmin <br>Password: $passwordAdmin <br><br><strong>Apaga este ficheiro (criar_admin.php) agora por segurança.</strong>";
