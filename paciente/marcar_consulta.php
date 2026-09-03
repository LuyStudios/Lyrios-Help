<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../gateways/GatewayFactory.php';
exigirTipo('paciente');
$titulo = "Marcar Consulta"; $pagina = 'marcar'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];

$psicologos = $pdo->query("
    SELECT u.id, u.nome, u.foto, p.especialidade, p.preco_sessao, p.status_personalizado,
        (SELECT COUNT(*) FROM certificados c WHERE c.psicologo_id = u.id AND c.estado='aprovado') AS verificado
    FROM utilizadores u JOIN perfis_psicologos p ON p.utilizador_id = u.id
    WHERE u.tipo='psicologo' AND u.estado='ativo' AND p.aprovado = 1
")->fetchAll();
foreach ($psicologos as &$p) { $p['avaliacao'] = mediaAvaliacoes($pdo, $p['id']); }
unset($p);

$psicologoPreSelecionado = validarInteiro($_GET['psicologo_id'] ?? 0, 1, null, 0);

$servicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1")->fetchAll();
$metodosAtivos = GatewayFactory::metodosAtivos($pdo);

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    $psicologo_id = (int)$_POST['psicologo_id'];
    $servico_id = (int)$_POST['servico_id'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $metodo = $_POST['metodo'];
    $telefone = limparTelefone($_POST['telefone_pagamento'] ?? '');
    $codigoCupao = strtoupper(trim($_POST['cupao'] ?? ''));
    $dataHora = $data . ' ' . $hora . ':00';

    $gatewaysValidos = array_column($metodosAtivos, 'gateway');

    $stmtP = $pdo->prepare("SELECT preco_sessao FROM perfis_psicologos WHERE utilizador_id = ?");
    $stmtP->execute([$psicologo_id]);
    $psic = $stmtP->fetch();

    if (!$psic) {
        $erro = 'Psicólogo inválido.';
    } elseif (!in_array($metodo, $gatewaysValidos, true)) {
        $erro = 'Método de pagamento inválido.';
    } elseif (strtotime($dataHora) < time()) {
        $erro = 'Escolhe uma data e hora futura.';
    } elseif ($metodo !== 'simulado' && strlen($telefone) < 9) {
        $erro = 'Indica um número de telemóvel válido para o pagamento.';
    } else {
        $disponibilidade = verificarDisponibilidadePsicologo($pdo, $psicologo_id, $dataHora);
        if ($disponibilidade !== true) {
            $erro = $disponibilidade;
        } else {
        // Valida o cupão, se indicado
        $cupao = null; $valorDesconto = 0;
        if ($codigoCupao !== '') {
            $stmt = $pdo->prepare("SELECT * FROM cupoes WHERE codigo = ? AND ativo = 1 AND (validade IS NULL OR validade >= CURDATE()) AND (usos_maximos IS NULL OR usos_atuais < usos_maximos)");
            $stmt->execute([$codigoCupao]);
            $cupao = $stmt->fetch();
            if (!$cupao) { $erro = 'Cupão inválido, expirado ou já esgotado.'; }
        }
        if ($erro === '') {
        $pdo->beginTransaction();
        try {
            $codigoSala = gerarCodigoSala();
            $stmt = $pdo->prepare("INSERT INTO consultas (paciente_id, psicologo_id, servico_id, data_hora, sala_codigo, estado) VALUES (?,?,?,?,?, 'pendente')");
            $stmt->execute([$uid, $psicologo_id, $servico_id ?: null, $dataHora, $codigoSala]);
            $consultaId = $pdo->lastInsertId();

            $valorBase = (float)$psic['preco_sessao'];
            if ($cupao) {
                $valorDesconto = round($valorBase * ($cupao['percentagem_desconto'] / 100), 2);
            }
            $valorTotal = round($valorBase - $valorDesconto, 2);
            $percentagem = percentagemComissao($pdo);
            $valorPlataforma = round($valorTotal * ($percentagem / 100), 2);
            $valorPsicologo = round($valorTotal - $valorPlataforma, 2);

            $stmt = $pdo->prepare("INSERT INTO pagamentos (consulta_id, valor_total, percentagem_plataforma, valor_plataforma, valor_psicologo, metodo, gateway, cupao_id, valor_desconto, estado) VALUES (?,?,?,?,?,?,?,?,?, 'pendente')");
            $metodoEnum = in_array($metodo, ['multicaixa'], true) ? 'multicaixa' : ($metodo === 'simulado' ? 'simulado' : 'cartao');
            $stmt->execute([$consultaId, $valorTotal, $percentagem, $valorPlataforma, $valorPsicologo, $metodoEnum, $metodo, $cupao['id'] ?? null, $valorDesconto]);
            $pagamentoId = $pdo->lastInsertId();

            if ($cupao) {
                $pdo->prepare("UPDATE cupoes SET usos_atuais = usos_atuais + 1 WHERE id = ?")->execute([$cupao['id']]);
            }

            $pdo->commit();

            // Aciona o gateway de pagamento escolhido
            $gateway = GatewayFactory::criar($pdo, $metodo);
            $retornoUrl = 'https://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/pagamentos/webhook.php?gateway=' . $metodo;
            $resultado = $gateway->iniciarPagamento([
                'referencia' => 'MC' . $pagamentoId,
                'valor' => $valorTotal,
                'telefone' => $telefone,
                'retorno_url' => $retornoUrl,
            ]);

            $pdo->prepare("UPDATE pagamentos SET referencia_gateway=?, payload_resposta=? WHERE id=?")
                ->execute([$resultado['referencia_gateway'], json_encode($resultado['payload']), $pagamentoId]);

            if (!$resultado['sucesso']) {
                $pdo->prepare("UPDATE pagamentos SET estado='falhado' WHERE id=?")->execute([$pagamentoId]);
                $erro = $resultado['mensagem'];
            } elseif ($resultado['estado'] === 'pago') {
                // Gateway simulado ou confirmação instantânea
                $pdo->prepare("UPDATE pagamentos SET estado='pago' WHERE id=?")->execute([$pagamentoId]);
                // A consulta continua "pendente": só fica "confirmada" depois de o psicólogo confirmar disponibilidade
                registarAtividade($pdo, $uid, 'Marcou uma consulta e efetuou o pagamento de ' . formatarKz($valorTotal) . '.');
                registarAtividade($pdo, $psicologo_id, 'Pagamento de uma nova consulta confirmado. Confirma a tua disponibilidade na Agenda.');
                header('Location: ' . BASE_URL . '/paciente/minhas_consultas.php?sucesso=1');
                exit;
            } elseif ($resultado['redirect_url']) {
                header('Location: ' . $resultado['redirect_url']);
                exit;
            } else {
                header('Location: ' . BASE_URL . '/pagamentos/estado.php?id=' . $pagamentoId);
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erro = 'Ocorreu um erro ao processar a marcação. Tenta novamente.';
        }
        }
        }
    }
}

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Marcar Consulta</h1>

<div class="wizard-box" style="margin:0;max-width:680px;">
  <div class="wizard-progress"><div class="wizard-progress-fill" id="agendaProgresso" style="width:25%;"></div></div>
  <div class="wizard-corpo">
    <?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>

    <?php if (empty($metodosAtivos)): ?>
      <div class="alert alert-error">Ainda não existe nenhum método de pagamento ativo. Contacta o administrador.</div>
    <?php elseif (empty($psicologos)): ?>
      <div class="alert alert-error">Ainda não há psicólogos disponíveis. Volta a tentar mais tarde.</div>
    <?php else: ?>
    <form method="post" id="formAgendar">
      <?php csrfCampo(); ?>
      <input type="hidden" name="psicologo_id" id="inputPsicologo" value="<?= $psicologoPreSelecionado ?: '' ?>">

      <!-- PASSO 1: escolher psicólogo -->
      <div class="agenda-passo ativo" data-passo="1">
        <p class="wizard-passo-contador">Passo 1 de 4</p>
        <h2 class="wizard-pergunta-titulo">Escolhe o teu psicólogo</h2>
        <div class="grid grid-2" style="gap:12px;margin-bottom:6px;">
          <?php foreach ($psicologos as $p): ?>
          <div class="opcao-psicologo <?= $psicologoPreSelecionado === (int)$p['id'] ? 'selecionado' : '' ?>" data-id="<?= $p['id'] ?>" data-preco="<?= formatarKz($p['preco_sessao']) ?>" onclick="escolherPsicologo(this)">
            <?php if ($p['foto']): ?>
              <img src="<?= BASE_URL ?>/<?= escape($p['foto']) ?>" alt="">
            <?php else: ?>
              <div class="placeholder"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
            <div class="info">
              <strong><?= escape($p['nome']) ?><?= $p['verificado'] > 0 ? ' <i class="fa-solid fa-shield-check" style="color:var(--success);font-size:11px;"></i>' : '' ?></strong>
              <span><?= escape($p['especialidade']) ?></span>
              <span class="preco"><?= formatarKz($p['preco_sessao']) ?><?= $p['avaliacao']['total'] > 0 ? ' · ★ ' . $p['avaliacao']['media'] : '' ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="wizard-nav" style="justify-content:flex-end;">
          <button type="button" class="btn btn-primary btn-seguinte" onclick="agendaAvancar(1)">Continuar</button>
        </div>
      </div>

      <!-- PASSO 2: serviço + data/hora -->
      <div class="agenda-passo" data-passo="2">
        <p class="wizard-passo-contador">Passo 2 de 4</p>
        <h2 class="wizard-pergunta-titulo">Quando queres ser atendido?</h2>
        <div class="form-group">
          <label>Serviço (opcional)</label>
          <select name="servico_id">
            <option value="">Geral</option>
            <?php foreach ($servicos as $s): ?>
              <option value="<?= $s['id'] ?>"><?= escape($s['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-2" style="gap:14px;">
          <div class="form-group"><label>Data</label><input type="date" name="data" min="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label>Hora</label><input type="time" name="hora" required></div>
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="agendaVoltar(2)"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="agendaAvancar(2)">Continuar</button>
        </div>
      </div>

      <!-- PASSO 3: pagamento -->
      <div class="agenda-passo" data-passo="3">
        <p class="wizard-passo-contador">Passo 3 de 4</p>
        <h2 class="wizard-pergunta-titulo">Como preferes pagar?</h2>
        <div class="chip-select" style="margin-bottom:22px;">
          <?php foreach ($metodosAtivos as $i => $m): ?>
            <div class="chip metodo-pagamento <?= $i === 0 ? 'selecionado' : '' ?>" data-valor="<?= escape($m['gateway']) ?>" onclick="escolherMetodo(this)"><?= escape($m['nome_visivel']) ?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="metodo" id="inputMetodo" value="<?= !empty($metodosAtivos) ? escape($metodosAtivos[0]['gateway']) : '' ?>">
        <div class="form-group" id="campoTelefone" style="<?= (!empty($metodosAtivos) && $metodosAtivos[0]['gateway'] === 'simulado') ? 'display:none;' : '' ?>">
          <label>Número de telemóvel para pagamento</label>
          <input type="text" name="telefone_pagamento" placeholder="9XXXXXXXX">
        </div>
        <div class="form-group">
          <label>Cupão de desconto (opcional)</label>
          <input type="text" name="cupao" placeholder="Ex: BEMVINDO10" style="text-transform:uppercase;">
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="agendaVoltar(3)"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="agendaAvancar(3)">Continuar</button>
        </div>
      </div>

      <!-- PASSO 4: confirmação -->
      <div class="agenda-passo" data-passo="4">
        <p class="wizard-passo-contador">Passo 4 de 4</p>
        <h2 class="wizard-pergunta-titulo">Confirma a tua marcação</h2>
        <div class="card" id="resumoAgenda" style="background:var(--primary-light);border:none;padding:22px;margin-bottom:22px;"></div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="agendaVoltar(4)"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button class="btn btn-primary" type="submit">Confirmar e Pagar</button>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<style>
  .opcao-psicologo{display:flex;gap:12px;align-items:center;padding:14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);cursor:pointer;}
  .opcao-psicologo:hover{border-color:var(--primary);}
  .opcao-psicologo.selecionado{background:var(--primary-light);border-color:var(--primary);}
  .opcao-psicologo img{width:46px;height:46px;border-radius:50%;object-fit:cover;flex-shrink:0;}
  .opcao-psicologo .placeholder{width:46px;height:46px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .opcao-psicologo .info{display:flex;flex-direction:column;min-width:0;}
  .opcao-psicologo .info strong{font-size:14px;color:var(--primary-dark);}
  .opcao-psicologo .info span{font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .opcao-psicologo .info .preco{font-weight:600;color:var(--accent);}
  @media (max-width:600px){.opcao-psicologo{padding:12px;}}
</style>

<script>
(function(){
  var passoAtual = 1;
  var totalPassos = 4;

  function mostrarPasso(n){
    document.querySelectorAll('.agenda-passo').forEach(function(el){
      el.classList.toggle('ativo', parseInt(el.getAttribute('data-passo')) === n);
    });
    document.getElementById('agendaProgresso').style.width = (n / totalPassos * 100) + '%';
    passoAtual = n;
    if (n === 4) montarResumo();
  }

  window.escolherPsicologo = function(el){
    document.querySelectorAll('.opcao-psicologo').forEach(function(o){ o.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    document.getElementById('inputPsicologo').value = el.getAttribute('data-id');
  };

  window.escolherMetodo = function(el){
    document.querySelectorAll('.metodo-pagamento').forEach(function(o){ o.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    var valor = el.getAttribute('data-valor');
    document.getElementById('inputMetodo').value = valor;
    document.getElementById('campoTelefone').style.display = valor === 'simulado' ? 'none' : 'block';
  };

  window.agendaAvancar = function(passo){
    if (passo === 1 && !document.getElementById('inputPsicologo').value) {
      alert('Escolhe um psicólogo para continuar.');
      return;
    }
    if (passo === 2) {
      var data = document.querySelector('input[name="data"]');
      var hora = document.querySelector('input[name="hora"]');
      if (!data.value || !hora.value) { alert('Indica a data e a hora da consulta.'); return; }
    }
    mostrarPasso(passo + 1);
  };
  window.agendaVoltar = function(passo){ mostrarPasso(passo - 1); };

  function montarResumo(){
    var nomePsicologo = '';
    var elPsicologo = document.querySelector('.opcao-psicologo.selecionado .info strong');
    if (elPsicologo) nomePsicologo = elPsicologo.textContent.trim();
    var data = document.querySelector('input[name="data"]').value;
    var hora = document.querySelector('input[name="hora"]').value;
    var metodoTexto = '';
    var elMetodo = document.querySelector('.metodo-pagamento.selecionado');
    if (elMetodo) metodoTexto = elMetodo.textContent.trim();

    document.getElementById('resumoAgenda').innerHTML =
      '<p style="margin:0 0 8px;"><strong>Psicólogo:</strong> ' + nomePsicologo + '</p>' +
      '<p style="margin:0 0 8px;"><strong>Data:</strong> ' + (data || '-') + ' às ' + (hora || '-') + '</p>' +
      '<p style="margin:0;"><strong>Pagamento:</strong> ' + metodoTexto + '</p>';
  }
})();
</script>

<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
