<?php
require_once __DIR__ . '/../includes/funcoes.php';
if (!estaLogado()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$codigo = $_GET['codigo'] ?? '';
if ($codigo === '') { die('Sala inválida.'); }

// Confirma que o utilizador pertence a esta consulta
$stmt = $pdo->prepare("SELECT c.*, up.nome AS nome_paciente, us.nome AS nome_psicologo FROM consultas c
    JOIN utilizadores up ON up.id = c.paciente_id
    JOIN utilizadores us ON us.id = c.psicologo_id
    WHERE c.sala_codigo = ?");
$stmt->execute([$codigo]);
$consulta = $stmt->fetch();

if (!$consulta) { die('Sala não encontrada.'); }

$uid = $_SESSION['utilizador_id'];
if ($uid != $consulta['paciente_id'] && $uid != $consulta['psicologo_id'] && $_SESSION['tipo'] !== 'admin') {
    die('Não tens permissão para aceder a esta sala.');
}

if ($consulta['estado'] !== 'confirmada' && $_SESSION['tipo'] !== 'admin') {
    die('Esta consulta ainda não foi confirmada. O psicólogo precisa de confirmar a disponibilidade antes da chamada ficar disponível.');
}

registarAtividade($pdo, $uid, 'Entrou na sala de videochamada da consulta #' . $consulta['id'] . '.');

$linkVoltar = $_SESSION['tipo'] === 'psicologo'
    ? BASE_URL . '/psicologo/agenda.php'
    : BASE_URL . '/paciente/minhas_consultas.php';

$outroNome = $_SESSION['tipo'] === 'psicologo' ? $consulta['nome_paciente'] : $consulta['nome_psicologo'];

$linkChat = null;
if ($_SESSION['tipo'] === 'paciente') $linkChat = BASE_URL . '/paciente/chat.php?id=' . $consulta['psicologo_id'];
elseif ($_SESSION['tipo'] === 'psicologo') $linkChat = BASE_URL . '/psicologo/chat.php?id=' . $consulta['paciente_id'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Consulta com <?= escape($outroNome) ?> - Lyrios</title>
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
  <a href="<?= escape($linkVoltar) ?>" class="marca-lyrios">
    <img src="<?= BASE_URL ?>/assets/img/logo-icone.png" alt="Lyrios">
    <span class="texto-marca">Lyrios</span>
  </a>
  <div class="info"><strong><?= escape($outroNome) ?></strong> · <?= date('d/m/Y H:i', strtotime($consulta['data_hora'])) ?></div>
  <div class="acoes">
    <?php if ($linkChat): ?>
    <a href="<?= escape($linkChat) ?>" class="btn btn-small btn-outline" style="border-color:rgba(255,255,255,.35);"><i class="fa-solid fa-comments"></i></a>
    <?php endif; ?>
    <a href="<?= escape($linkVoltar) ?>" class="btn btn-small btn-outline" style="border-color:rgba(255,255,255,.35);"><i class="fa-solid fa-xmark"></i> Sair</a>
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
      roomName: <?= json_encode($codigo) ?>,
      parentNode: document.getElementById('jitsiContainer'),
      width: '100%',
      height: '100%',
      userInfo: { displayName: <?= json_encode($_SESSION['nome']) ?> },
      configOverwrite: {
        disableDeepLinking: true,      // evita que telemóveis tentem abrir a app nativa e quebrem a chamada
        prejoinPageEnabled: true,      // deixa cada pessoa escolher câmara/microfone antes de entrar (importante em tablets com várias câmaras)
        disableInviteFunctions: true,  // a sala é privada desta consulta, não faz sentido convidar mais gente
        startWithVideoMuted: false,
        startWithAudioMuted: false
      },
      interfaceConfigOverwrite: {
        MOBILE_APP_PROMO: false,       // remove o popup "instala a app" que atrapalha em telemóvel
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false
      }
    });

    // Quando alguém sai da chamada, volta automaticamente para a plataforma
    api.addEventListener('readyToClose', function(){
      window.location.href = <?= json_encode($linkVoltar) ?>;
    });
  })();
</script>
</body>
</html>
