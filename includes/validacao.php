<?php
/**
 * VALIDAÇÃO E SANITIZAÇÃO DE ENTRADAS
 * Usadas em conjunto com os prepared statements (PDO) para reforçar
 * a proteção contra SQL Injection e dados inválidos.
 */

/** Garante que um valor está dentro de uma lista de valores permitidos (whitelist). Devolve o valor por defeito se não estiver. */
function validarLista($valor, array $permitidos, $padrao) {
    return in_array($valor, $permitidos, true) ? $valor : $padrao;
}

/** Converte para inteiro de forma segura, com limites opcionais */
function validarInteiro($valor, $minimo = null, $maximo = null, $padrao = 0) {
    if (!is_numeric($valor)) return $padrao;
    $n = (int)$valor;
    if ($minimo !== null && $n < $minimo) return $padrao;
    if ($maximo !== null && $n > $maximo) return $padrao;
    return $n;
}

/** Valida uma data no formato Y-m-d */
function validarData($valor) {
    $d = DateTime::createFromFormat('Y-m-d', $valor);
    return ($d && $d->format('Y-m-d') === $valor) ? $valor : false;
}

/** Calcula a idade em anos a partir de uma data de nascimento (Y-m-d) */
function calcularIdade($dataNascimento) {
    $nascimento = DateTime::createFromFormat('Y-m-d', $dataNascimento);
    if (!$nascimento) return 0;
    return $nascimento->diff(new DateTime())->y;
}
