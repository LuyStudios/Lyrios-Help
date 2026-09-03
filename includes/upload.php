<?php
/**
 * Funções de upload seguro de ficheiros.
 * - Nunca confiam na extensão nem no nome original do ficheiro.
 * - Validam sempre o tipo real do ficheiro (MIME / assinatura interna).
 * - Geram um nome aleatório para o ficheiro guardado, evitando colisões
 *   e impedindo que se adivinhe o nome de ficheiros de outras pessoas.
 */

/** Faz upload de uma foto de perfil (paciente ou psicólogo). Devolve caminho relativo ou erro. */
function uploadFoto($campoNome) {
    if (empty($_FILES[$campoNome]) || $_FILES[$campoNome]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['sucesso' => false, 'erro' => null, 'caminho' => null];
    }
    $arquivo = $_FILES[$campoNome];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'erro' => 'Ocorreu um erro ao enviar a imagem.', 'caminho' => null];
    }
    if ($arquivo['size'] > 3 * 1024 * 1024) {
        return ['sucesso' => false, 'erro' => 'A foto não pode exceder 3 MB.', 'caminho' => null];
    }

    // getimagesize() valida que o conteúdo é mesmo uma imagem (não confia na extensão)
    $infoImagem = @getimagesize($arquivo['tmp_name']);
    if (!$infoImagem) {
        return ['sucesso' => false, 'erro' => 'O ficheiro enviado não é uma imagem válida.', 'caminho' => null];
    }

    $mimesPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = $infoImagem['mime'];
    if (!isset($mimesPermitidos[$mime])) {
        return ['sucesso' => false, 'erro' => 'Formato não permitido. Usa JPG, PNG ou WEBP.', 'caminho' => null];
    }

    $nomeFicheiro = 'foto_' . bin2hex(random_bytes(12)) . '.' . $mimesPermitidos[$mime];
    $destino = __DIR__ . '/../uploads/fotos/' . $nomeFicheiro;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return ['sucesso' => false, 'erro' => 'Não foi possível guardar a imagem no servidor.', 'caminho' => null];
    }
    return ['sucesso' => true, 'erro' => null, 'caminho' => 'uploads/fotos/' . $nomeFicheiro];
}

/** Faz upload de um certificado/documento profissional (PDF, JPG ou PNG). */
function uploadCertificado($campoNome) {
    $vazio = ['sucesso' => false, 'erro' => null, 'caminho' => null, 'nome_original' => null, 'tipo' => null];

    if (empty($_FILES[$campoNome]) || $_FILES[$campoNome]['error'] === UPLOAD_ERR_NO_FILE) {
        return $vazio;
    }
    $arquivo = $_FILES[$campoNome];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $vazio['erro'] = 'Ocorreu um erro ao enviar o documento.';
        return $vazio;
    }
    if ($arquivo['size'] > 8 * 1024 * 1024) {
        $vazio['erro'] = 'O documento não pode exceder 8 MB.';
        return $vazio;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    $mimesPermitidos = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($mimesPermitidos[$mime])) {
        $vazio['erro'] = 'Formato não permitido. Envia um PDF, JPG ou PNG.';
        return $vazio;
    }

    $nomeFicheiro = 'cert_' . bin2hex(random_bytes(12)) . '.' . $mimesPermitidos[$mime];
    $destino = __DIR__ . '/../uploads/certificados/' . $nomeFicheiro;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $vazio['erro'] = 'Não foi possível guardar o documento no servidor.';
        return $vazio;
    }

    return [
        'sucesso' => true, 'erro' => null,
        'caminho' => 'uploads/certificados/' . $nomeFicheiro,
        'nome_original' => basename($arquivo['name']),
        'tipo' => $mime,
    ];
}

/** Faz upload de um áudio de mensagem de chat (gravado no browser). */
function uploadAudioChat($campoNome) {
    $vazio = ['sucesso' => false, 'erro' => null, 'caminho' => null, 'convertido' => false];

    if (empty($_FILES[$campoNome]) || $_FILES[$campoNome]['error'] === UPLOAD_ERR_NO_FILE) {
        $vazio['erro'] = 'Nenhum áudio recebido.';
        return $vazio;
    }
    $arquivo = $_FILES[$campoNome];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $vazio['erro'] = 'Ocorreu um erro ao enviar o áudio.';
        return $vazio;
    }
    if ($arquivo['size'] > 10 * 1024 * 1024) {
        $vazio['erro'] = 'O áudio não pode exceder 10 MB.';
        return $vazio;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    // Cada dispositivo/navegador grava num formato diferente:
    // Chrome/Android e Edge -> webm; Firefox -> webm ou ogg; Safari/iPhone -> mp4.
    $mimesPermitidos = [
        'audio/webm' => 'webm', 'audio/ogg' => 'ogg', 'video/webm' => 'webm',
        'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a',
        'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/aac' => 'aac',
    ];
    if (!isset($mimesPermitidos[$mime])) {
        $vazio['erro'] = 'Formato de áudio não suportado.';
        return $vazio;
    }

    $nomeBase = 'audio_' . bin2hex(random_bytes(12));
    $nomeOriginal = $nomeBase . '.' . $mimesPermitidos[$mime];
    $destinoOriginal = __DIR__ . '/../uploads/audios/' . $nomeOriginal;

    if (!move_uploaded_file($arquivo['tmp_name'], $destinoOriginal)) {
        $vazio['erro'] = 'Não foi possível guardar o áudio no servidor.';
        return $vazio;
    }

    // IMPORTANTE PARA COMPATIBILIDADE ENTRE DISPOSITIVOS:
    // o Safari (iPhone/iPad/Mac) não consegue reproduzir ficheiros .webm de todo,
    // por isso convertemos sempre para .mp3 (formato universal, toca em qualquer
    // telemóvel, tablet ou computador). Se o servidor não tiver o ffmpeg instalado,
    // mantemos o ficheiro original (pode não tocar em todos os dispositivos).
    $destinoMp3 = __DIR__ . '/../uploads/audios/' . $nomeBase . '.mp3';
    if (transcodificarAudioParaMp3($destinoOriginal, $destinoMp3)) {
        @unlink($destinoOriginal);
        return ['sucesso' => true, 'erro' => null, 'caminho' => 'uploads/audios/' . $nomeBase . '.mp3', 'convertido' => true];
    }

    return ['sucesso' => true, 'erro' => null, 'caminho' => 'uploads/audios/' . $nomeOriginal, 'convertido' => false];
}

/** Verifica se uma função nativa do PHP está disponível (não desativada em disable_functions) */
function funcaoPhpDisponivel($nome) {
    if (!function_exists($nome)) return false;
    $desativadas = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array($nome, $desativadas, true);
}

/**
 * Converte um ficheiro de áudio para MP3 usando o ffmpeg, se estiver disponível
 * no servidor. Devolve true se a conversão foi feita com sucesso.
 */
function transcodificarAudioParaMp3($caminhoOrigem, $caminhoDestino) {
    if (!funcaoPhpDisponivel('shell_exec')) return false;

    $verificacao = @shell_exec('ffmpeg -version 2>&1');
    if (!$verificacao || stripos($verificacao, 'ffmpeg version') === false) return false;

    $comando = 'ffmpeg -y -i ' . escapeshellarg($caminhoOrigem) .
        ' -vn -ar 44100 -ac 1 -b:a 64k ' . escapeshellarg($caminhoDestino) . ' 2>&1';
    @shell_exec($comando);

    return file_exists($caminhoDestino) && filesize($caminhoDestino) > 0;
}
