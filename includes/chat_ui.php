<?php
/**
 * Espera as variáveis:
 * $conversaId, $outroNome, $outroFoto (caminho ou null), $mensagensIniciais (array), $voltarUrl
 */
?>
<div class="chat-janela">
  <div class="chat-cabecalho">
    <?php if ($outroFoto): ?>
      <img src="<?= BASE_URL ?>/<?= escape($outroFoto) ?>" class="chat-avatar">
    <?php else: ?>
      <div class="chat-avatar-placeholder"><i class="fa-solid fa-user"></i></div>
    <?php endif; ?>
    <div class="nome"><?= escape($outroNome) ?></div>
    <a href="<?= BASE_URL ?>/chamada/conversa.php?id=<?= (int)$conversaId ?>" target="_blank" class="btn btn-small btn-primary" style="margin-left:auto;"><i class="fa-solid fa-video"></i> Chamar</a>
    <a href="<?= escape($voltarUrl) ?>" style="color:#1f6f5c;font-size:13px;font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
  </div>

  <div class="chat-mensagens" id="chatMensagens"></div>

  <div class="chat-rodape">
    <button type="button" class="chat-botao-icone chat-botao-audio" id="btnGravar" title="Gravar mensagem de áudio">
      <i class="fa-solid fa-microphone"></i>
    </button>
    <span class="chat-gravando-aviso" id="avisoGravando" style="display:none;">A gravar...</span>
    <input type="text" id="campoMensagem" placeholder="Escreve uma mensagem...">
    <button type="button" class="chat-botao-icone chat-botao-enviar" id="btnEnviar" title="Enviar">
      <i class="fa-solid fa-paper-plane"></i>
    </button>
  </div>
</div>

<script>
(function () {
  var conversaId = <?= (int)$conversaId ?>;
  var csrfToken = <?= json_encode(csrfToken()) ?>;
  var baseUrl = <?= json_encode(BASE_URL) ?>;
  var ultimaId = 0;
  var container = document.getElementById('chatMensagens');

  function escaparHtml(txt) {
    var d = document.createElement('div');
    d.innerText = txt;
    return d.innerHTML;
  }

  function adicionarMensagem(m) {
    var bolha = document.createElement('div');
    bolha.className = 'chat-bolha ' + (m.sou_eu ? 'enviada' : 'recebida');
    var corpo = '';
    if (m.tipo === 'audio') {
      corpo = '<audio controls src="' + m.audio_url + '"></audio>';
    } else if (m.tipo === 'chamada') {
      corpo = '<div class="chat-chamada-aviso">' +
                '<i class="fa-solid fa-video"></i> ' + (m.sou_eu ? 'Iniciaste uma chamada' : 'Chamada recebida') +
                '<a href="' + m.conteudo + '" target="_blank" class="btn btn-small btn-primary" style="margin-top:8px;display:inline-block;">Entrar na chamada</a>' +
              '</div>';
    } else {
      corpo = escaparHtml(m.conteudo).replace(/\n/g, '<br>');
    }
    bolha.innerHTML = corpo + '<span class="hora">' + m.hora + '</span>';
    container.appendChild(bolha);
    if (m.id > ultimaId) ultimaId = m.id;
  }

  // Mensagens iniciais (carregadas do lado do servidor)
  var iniciais = <?= json_encode(array_map(function ($m) {
      return [
          'id' => (int)$m['id'],
          'sou_eu' => (int)$m['remetente_id'] === (int)$_SESSION['utilizador_id'],
          'tipo' => $m['tipo'],
          'conteudo' => $m['conteudo'],
          'audio_url' => $m['tipo'] === 'audio' ? (BASE_URL . '/chat/audio.php?id=' . $m['id']) : null,
          'hora' => date('H:i', strtotime($m['criado_em'])),
      ];
  }, $mensagensIniciais)) ?>;
  iniciais.forEach(adicionarMensagem);
  container.scrollTop = container.scrollHeight;

  function buscarNovas() {
    fetch(baseUrl + '/chat/buscar_mensagens.php?conversa_id=' + conversaId + '&ultima_id=' + ultimaId)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.sucesso && data.mensagens.length) {
          data.mensagens.forEach(adicionarMensagem);
          container.scrollTop = container.scrollHeight;
        }
      })
      .catch(function () {});
  }
  setInterval(buscarNovas, 3000);

  // Enviar texto
  var campo = document.getElementById('campoMensagem');
  function enviarTexto() {
    var texto = campo.value.trim();
    if (!texto) return;
    campo.value = '';
    var fd = new FormData();
    fd.append('conversa_id', conversaId);
    fd.append('mensagem', texto);
    fd.append('csrf_token', csrfToken);
    fetch(baseUrl + '/chat/enviar_texto.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.sucesso) {
          adicionarMensagem({ id: data.id, sou_eu: true, tipo: 'texto', conteudo: texto, hora: data.criado_em });
          container.scrollTop = container.scrollHeight;
        } else {
          alert(data.erro || 'Não foi possível enviar a mensagem.');
        }
      });
  }
  document.getElementById('btnEnviar').addEventListener('click', enviarTexto);
  campo.addEventListener('keydown', function (e) { if (e.key === 'Enter') enviarTexto(); });

  // Gravação de áudio
  var gravando = false, mediaRecorder = null, chunks = [], inicioGravacao = 0;
  var btnGravar = document.getElementById('btnGravar');
  var avisoGravando = document.getElementById('avisoGravando');

  // Escolhe explicitamente o melhor formato suportado pelo navegador/dispositivo atual,
  // em vez de deixar ao critério do browser (evita incompatibilidades silenciosas).
  function escolherFormatoGravacao() {
    if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) {
      return { mimeType: '', extensao: 'webm' }; // deixa o browser decidir (fallback mais antigo)
    }
    var candidatos = [
      { mimeType: 'audio/webm;codecs=opus', extensao: 'webm' }, // Chrome, Edge, Android
      { mimeType: 'audio/webm', extensao: 'webm' },
      { mimeType: 'audio/ogg;codecs=opus', extensao: 'ogg' },   // Firefox
      { mimeType: 'audio/mp4', extensao: 'm4a' },               // Safari (iPhone/iPad/Mac)
      { mimeType: 'audio/aac', extensao: 'aac' }
    ];
    for (var i = 0; i < candidatos.length; i++) {
      if (MediaRecorder.isTypeSupported(candidatos[i].mimeType)) return candidatos[i];
    }
    return { mimeType: '', extensao: 'webm' };
  }

  btnGravar.addEventListener('click', function () {
    if (!gravando) iniciarGravacao(); else pararGravacao();
  });

  function iniciarGravacao() {
    if (typeof MediaRecorder === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Este navegador não suporta gravação de áudio. Tenta atualizar o navegador ou usa outro dispositivo.');
      return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
      chunks = [];
      var formato = escolherFormatoGravacao();
      try {
        mediaRecorder = formato.mimeType ? new MediaRecorder(stream, { mimeType: formato.mimeType }) : new MediaRecorder(stream);
      } catch (erroFormato) {
        mediaRecorder = new MediaRecorder(stream); // último recurso: deixa o browser escolher
      }
      mediaRecorder.ondataavailable = function (e) { if (e.data.size > 0) chunks.push(e.data); };
      mediaRecorder.onstop = function () {
        stream.getTracks().forEach(function (t) { t.stop(); });
        var duracao = Math.round((Date.now() - inicioGravacao) / 1000);
        var tipoFinal = mediaRecorder.mimeType || formato.mimeType || 'audio/webm';
        var blob = new Blob(chunks, { type: tipoFinal });
        enviarAudio(blob, duracao, formato.extensao);
      };
      mediaRecorder.start();
      inicioGravacao = Date.now();
      gravando = true;
      btnGravar.classList.add('a-gravar');
      btnGravar.innerHTML = '<i class="fa-solid fa-stop"></i>';
      avisoGravando.style.display = 'inline';
    }).catch(function () {
      alert('Não foi possível aceder ao microfone. Verifica as permissões do navegador para este site.');
    });
  }

  function pararGravacao() {
    if (mediaRecorder && gravando) mediaRecorder.stop();
    gravando = false;
    btnGravar.classList.remove('a-gravar');
    btnGravar.innerHTML = '<i class="fa-solid fa-microphone"></i>';
    avisoGravando.style.display = 'none';
  }

  function enviarAudio(blob, duracao, extensao) {
    if (blob.size === 0) { alert('A gravação ficou vazia. Tenta novamente.'); return; }
    var fd = new FormData();
    fd.append('conversa_id', conversaId);
    fd.append('duracao', duracao);
    fd.append('csrf_token', csrfToken);
    fd.append('audio', blob, 'mensagem.' + (extensao || 'webm'));
    fetch(baseUrl + '/chat/enviar_audio.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.sucesso) {
          adicionarMensagem({ id: data.id, sou_eu: true, tipo: 'audio', audio_url: baseUrl + '/chat/audio.php?id=' + data.id, hora: data.criado_em });
          container.scrollTop = container.scrollHeight;
        } else {
          alert(data.erro || 'Não foi possível enviar o áudio.');
        }
      })
      .catch(function () {
        alert('Falha de ligação ao enviar o áudio. Verifica a tua internet e tenta novamente.');
      });
  }
})();
</script>
