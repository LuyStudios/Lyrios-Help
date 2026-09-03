<?php
/**
 * Ligação à base de dados MySQL via PDO
 * Ajusta estes dados para o teu servidor
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'mindcare');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Desativa a emulação de prepared statements: os parâmetros são
            // sempre enviados separados do SQL e validados pelo próprio MySQL,
            // o que reforça a proteção contra SQL Injection.
            PDO::ATTR_EMULATE_PREPARES => false,
            // Impede ataques de "multi-statement" (ex: "1; DROP TABLE ...")
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro de ligação à base de dados: " . $e->getMessage());
}
