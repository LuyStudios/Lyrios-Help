<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../chat/util.php';
if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$conversaId = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['utilizador_id'];

$stmt = $pdo->prepare("SELECT * FROM conversas WHERE id = ?");
$stmt->execute([$conversaId]);
$conversa = $stmt->fetch();

if (!utilizadorPertenceConversa($conversa, $uid)) {
    die('Não tens permissão para aceder a esta chamada.');
}

// Gera a sala se ainda não existir (conversas criadas antes desta funcionalidade)
if (empty($conversa['sala_codigo'])) {
    $novoCodigo = gerarCodigoSala();
    $pdo->prepare("UPDATE conversas SET sala_codigo = ? WHERE id = ?")->execute([$novoCodigo, $conversaId]);
    $conversa['sala_codigo'] = $novoCodigo;
}

$souPaciente = (int)$conversa['paciente_id'] === (int)$uid;
$outroId = $souPaciente ? $conversa['psicologo_id'] : $conversa['paciente_id'];
$stmt = $pdo->prepare("SELECT nome FROM utilizadores WHERE id = ?");
$stmt->execute([$outroId]);
$outroNome = $stmt->fetch()['nome'];

$linkVoltarChat = BASE_URL . '/' . ($souPaciente ? 'paciente' : 'psicologo') . '/chat.php?id=' . $outroId;

// Avisa a outra pessoa no chat que uma chamada foi iniciada,
// mas só uma vez a cada 2 minutos para não repetir a cada recarregamento da página
$linkChamada = BASE_URL . '/chamada/conversa.php?id=' . $conversaId;
$stmt = $pdo->prepare("SELECT criado_em FROM mensagens_chat WHERE conversa_id = ? AND tipo = 'chamada' ORDER BY id DESC LIMIT 1");
$stmt->execute([$conversaId]);
$ultimaChamada = $stmt->fetch();

if (!$ultimaChamada || strtotime($ultimaChamada['criado_em']) < time() - 120) {
    $pdo->prepare("INSERT INTO mensagens_chat (conversa_id, remetente_id, tipo, conteudo) VALUES (?,?,'chamada',?)")
        ->execute([$conversaId, $uid, $linkChamada]);
    registarAtividade($pdo, $uid, 'Iniciou uma chamada de vídeo com ' . $outroNome . '.');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Chamada com <?= escape($outroNome) ?> - Lyrios</title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon-32.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  body{background:#0a1512;overflow:hidden;height:100vh;}
  .chamada-topo{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;color:#fff;}
  .chamada-topo .marca-lyrios img{height:30px;width:30px;}
  .chamada-topo .marca-lyrios .texto-marca{font-size:19px;}
  .chamada-topo .info{text-align:center;font-size:13px;color:rgba(255,255,255,.6);}
  .chamada-topo .info strong{color:#fff;font-weight:600;}
  .chamada-topo .acoes{display:flex;gap:10px;align-items:center;}
  .chamada-palco{height:calc(100vh - 66px);padding:0 20px 20px;}
  .chamada-palco .video-room{height:100%;border-radius:20px;}
  .chamada-aviso{font-size:12px;color:rgba(255,255,255,.4);text-align:center;padding:0 20px 14px;}
  @media (max-width:700px){
    .chamada-topo{flex-wrap:wrap;gap:8px;padding:12px 16px;}
    .chamada-topo .info{order:3;width:100%;text-align:left;}
    .chamada-palco{padding:0 10px 10px;height:calc(100vh - 118px);}
  }
</style>
</head>
<body>
<div class="chamada-topo">
  <a href="<?= escape($linkVoltarChat) ?>" class="marca-lyrios">
    <img src="<?= BASE_URL ?>/assets/img/logo-icone.png" alt="Lyrios">
    <span class="texto-marca">Lyrios</span>
  </a>
  <div class="info"><strong><?= escape($outroNome) ?></strong> · sala privada da conversa</div>
  <div class="acoes">
    <a href="<?= escape($linkVoltarChat) ?>" class="btn btn-small btn-outline" style="border-color:rgba(255,255,255,.35);"><i class="fa-solid fa-xmark"></i> Sair</a>
  </div>
</div>

<div class="chamada-palco">
  <div class="video-room" style="position:relative;">
    <div id="jitsiContainer" style="width:100%;height:100%;"></div>
    <div class="marca-agua" id="marcaAgua"></div>
  </div>
</div>
<p class="chamada-aviso"><i class="fa-solid fa-circle-info"></i> A gravação ou captura de ecrã desta chamada não é permitida pelos termos de uso da Lyrios.</p>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
  document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
  (function(){
    var marca = document.getElementById('marcaAgua');
    if (marca) {
      function atualizarMarca(){ marca.textContent = <?= json_encode($_SESSION['nome']) ?> + ' • ' + new Date().toLocaleString('pt-PT'); }
      atualizarMarca();
      setInterval(atualizarMarca, 15000);
    }

    if (typeof JitsiMeetExternalAPI === 'undefined') {
      document.getElementById('jitsiContainer').innerHTML =
        '<p style="color:#fff;padding:30px;text-align:center;">Não foi possível carregar a videochamada. Verifica a tua ligação à internet e recarrega a página.</p>';
      return;
    }

    var api = new JitsiMeetExternalAPI('meet.jit.si', {
      roomName: <?= json_encode($conversa['sala_codigo']) ?>,
      parentNode: document.getElementById('jitsiContainer'),
      width: '100%',
      height: '100%',
      userInfo: { displayName: <?= json_encode($_SESSION['nome']) ?> },
      configOverwrite: {
        disableDeepLinking: true,
        prejoinPageEnabled: true,
        disableInviteFunctions: true,
        startWithVideoMuted: false,
        startWithAudioMuted: false
      },
      interfaceConfigOverwrite: {
        MOBILE_APP_PROMO: false,
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false
      }
    });

    api.addEventListener('readyToClose', function(){
      window.location.href = <?= json_encode($linkVoltarChat) ?>;
    });
  })();
</script>
</body>
</html>
