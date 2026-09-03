<?php
$titulo = "Criar Conta";
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/upload.php';

if (estaLogado()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$tipoInicial = ($_GET['tipo'] ?? '');
$tipoInicial = in_array($tipoInicial, ['paciente', 'psicologo']) ? $tipoInicial : '';
$erro = '';

// Perguntas extra criadas pelo administrador, para cada tipo de utilizador
$perguntasPorTipo = ['paciente' => [], 'psicologo' => []];
$stmt = $pdo->query("SELECT * FROM perguntas_registo WHERE ativo = 1 ORDER BY tipo_utilizador ASC, ordem ASC");
foreach ($stmt->fetchAll() as $p) {
    $perguntasPorTipo[$p['tipo_utilizador']][] = $p;
}

/** Gera o HTML de um passo do wizard para uma pergunta extra definida pelo admin */
function renderPerguntaExtra($p) {
    $opcoes = array_values(array_filter(array_map('trim', explode(',', $p['opcoes'] ?? ''))));
    $classeColunas = count($opcoes) >= 4 ? 'col-4' : (count($opcoes) === 3 ? 'col-3' : '');
    ob_start();
    ?>
    <div class="wizard-step" data-tipo="<?= escape($p['tipo_utilizador']) ?>">
      <p class="wizard-passo-contador">Mais sobre ti</p>
      <h2 class="wizard-pergunta-titulo"><?= escape($p['pergunta']) ?></h2>
      <?php if ($p['tipo_campo'] === 'chip_multipla'): ?>
        <p class="wizard-pergunta-sub">Escolhe todas as opções que se aplicam<?= $p['obrigatorio'] ? '.' : ' (opcional).' ?></p>
        <div class="chip-select">
          <?php foreach ($opcoes as $o): ?>
            <div class="chip" data-valor="<?= escape($o) ?>" onclick="alternarChipExtra(this, <?= (int)$p['id'] ?>)"><?= escape($o) ?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="pergunta_extra_<?= (int)$p['id'] ?>" id="input_extra_<?= (int)$p['id'] ?>" value="">
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="avancarChipExtra(<?= (int)$p['id'] ?>, <?= $p['obrigatorio'] ? 1 : 0 ?>)">Continuar</button>
        </div>
      <?php elseif ($p['tipo_campo'] === 'cartao_unica'): ?>
        <div class="opcoes-cartao <?= $classeColunas ?>">
          <?php foreach ($opcoes as $o): ?>
            <div class="opcao-cartao" data-valor="<?= escape($o) ?>" onclick="escolherCartaoExtra(this, <?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($o), ENT_QUOTES) ?>)"><?= escape($o) ?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="pergunta_extra_<?= (int)$p['id'] ?>" id="input_extra_<?= (int)$p['id'] ?>" value="">
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <?php if (!$p['obrigatorio']): ?><button type="button" class="btn btn-primary" onclick="avancar()">Saltar</button><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="form-group">
          <textarea name="pergunta_extra_<?= (int)$p['id'] ?>" <?= $p['obrigatorio'] ? 'required' : '' ?> placeholder="A tua resposta..."></textarea>
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    $tipo = in_array($_POST['tipo'] ?? '', ['paciente', 'psicologo']) ? $_POST['tipo'] : 'paciente';
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telefone = limparTelefone($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    $especialidade = trim($_POST['especialidade'] ?? '');
    $abordagens = trim($_POST['abordagens_terapeuticas'] ?? '');
    $anosExperiencia = validarLista($_POST['anos_experiencia'] ?? '', ['0-2', '3-5', '6-10', '10+'], '');
    $biografia = trim($_POST['biografia'] ?? '');
    $preco = $_POST['preco_sessao'] ?? 5000;
    $dataNascimento = validarData($_POST['data_nascimento'] ?? '');

    $motivoProcura = $tipo === 'paciente' ? trim($_POST['motivo_procura'] ?? '') : null;
    $experienciaPrevia = ($tipo === 'paciente' && $_POST['experiencia_terapia_previa'] !== '') ? validarInteiro($_POST['experiencia_terapia_previa'] ?? '', 0, 1, null) : null;
    $preferenciaGenero = validarLista($_POST['preferencia_genero_psicologo'] ?? 'sem_preferencia', ['sem_preferencia', 'feminino', 'masculino'], 'sem_preferencia');

    // Respostas às perguntas extra criadas pelo administrador
    $respostasExtra = [];
    $erroPerguntaExtra = '';
    foreach ($perguntasPorTipo[$tipo] as $pExtra) {
        $valorResposta = trim($_POST['pergunta_extra_' . $pExtra['id']] ?? '');
        if ($pExtra['obrigatorio'] && $valorResposta === '' && $erroPerguntaExtra === '') {
            $erroPerguntaExtra = 'Responde à pergunta: "' . $pExtra['pergunta'] . '"';
        }
        if ($valorResposta !== '') $respostasExtra[$pExtra['id']] = mb_substr($valorResposta, 0, 1000);
    }

    $certificadoEnviado = !empty($_FILES['certificado']['name']) && $_FILES['certificado']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($nome === '' || !$email || strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $erro = 'Preenche todos os campos corretamente. A password deve ter pelo menos 8 caracteres, com letras e números.';
    } elseif (!$dataNascimento) {
        $erro = 'Indica a tua data de nascimento.';
    } elseif (calcularIdade($dataNascimento) < 16) {
        $erro = 'É necessário ter pelo menos 16 anos para criar uma conta na Lyrios.';
    } elseif ($tipo === 'psicologo' && $especialidade === '') {
        $erro = 'Escolhe pelo menos uma área de especialidade.';
    } elseif ($tipo === 'psicologo' && !$certificadoEnviado) {
        $erro = 'Como psicólogo, precisas de anexar um documento que comprove a tua qualificação profissional (diploma ou cédula).';
    } elseif ($erroPerguntaExtra !== '') {
        $erro = $erroPerguntaExtra;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Já existe uma conta com este email.';
        } else {
            $resultadoCertificado = null;
            if ($tipo === 'psicologo') {
                $resultadoCertificado = uploadCertificado('certificado');
                if ($resultadoCertificado['erro']) {
                    $erro = $resultadoCertificado['erro'];
                }
            }

            if ($erro === '') {
                $resultadoFoto = uploadFoto('foto');

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $estadoConta = $tipo === 'psicologo' ? 'pendente' : 'ativo';
                $stmt = $pdo->prepare("
                    INSERT INTO utilizadores (nome, email, password, telefone, tipo, estado, foto, data_nascimento, motivo_procura, experiencia_terapia_previa, preferencia_genero_psicologo)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([$nome, $email, $hash, $telefone, $tipo, $estadoConta, $resultadoFoto['caminho'], $dataNascimento, $motivoProcura, $experienciaPrevia, $preferenciaGenero]);
                $novoId = $pdo->lastInsertId();

                $tokenEmail = bin2hex(random_bytes(32));
                $pdo->prepare("INSERT INTO verificacoes_email (utilizador_id, token) VALUES (?,?)")->execute([$novoId, $tokenEmail]);
                $linkVerificacao = 'https://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/verificar_email.php?token=' . $tokenEmail;
                @mail($email, 'Confirma o teu email - Lyrios', "Olá $nome,\n\nConfirma o teu email clicando no link:\n$linkVerificacao");

                // Guarda as respostas às perguntas extra definidas pelo administrador
                foreach ($respostasExtra as $perguntaId => $resposta) {
                    $pdo->prepare("INSERT INTO respostas_registo (utilizador_id, pergunta_id, resposta) VALUES (?,?,?)")
                        ->execute([$novoId, $perguntaId, $resposta]);
                }

                if ($tipo === 'psicologo') {
                    $stmt = $pdo->prepare("
                        INSERT INTO perfis_psicologos (utilizador_id, especialidade, abordagens_terapeuticas, anos_experiencia, biografia, preco_sessao, aprovado)
                        VALUES (?,?,?,?,?,?,0)
                    ");
                    $stmt->execute([$novoId, $especialidade, $abordagens, $anosExperiencia ?: null, $biografia, $preco]);

                    $stmt = $pdo->prepare("INSERT INTO certificados (psicologo_id, nome_original, caminho, tipo, estado) VALUES (?,?,?,?,'pendente')");
                    $stmt->execute([$novoId, $resultadoCertificado['nome_original'], $resultadoCertificado['caminho'], $resultadoCertificado['tipo']]);

                    registarAtividade($pdo, $novoId, 'Conta de psicólogo criada, aguardando aprovação do administrador.');
                    header('Location: ' . BASE_URL . '/auth/login.php?registado=psicologo');
                    exit;
                } else {
                    registarAtividade($pdo, $novoId, 'Conta criada na plataforma.');
                    session_regenerate_id(true);
                    $_SESSION['utilizador_id'] = $novoId;
                    $_SESSION['nome'] = $nome;
                    $_SESSION['tipo'] = 'paciente';
                    header('Location: ' . BASE_URL . '/paciente/dashboard.php');
                    exit;
                }
            }
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="wizard-box">
  <div class="wizard-progress"><div class="wizard-progress-fill" id="barraProgresso" style="width:14%;"></div></div>
  <div class="wizard-corpo">
    <?php if ($erro): ?><div class="wizard-erro visivel"><?= escape($erro) ?></div><?php endif; ?>
    <div class="wizard-erro" id="erroWizard"></div>

    <form method="post" enctype="multipart/form-data" id="formRegisto" novalidate>
      <?php csrfCampo(); ?>
      <input type="hidden" name="tipo" id="tipoInput" value="<?= escape($tipoInicial) ?>">
      <input type="hidden" name="motivo_procura" id="motivoProcuraInput" value="">
      <input type="hidden" name="experiencia_terapia_previa" id="experienciaInput" value="">
      <input type="hidden" name="preferencia_genero_psicologo" id="preferenciaInput" value="sem_preferencia">
      <input type="hidden" name="especialidade" id="especialidadeInput" value="">
      <input type="hidden" name="abordagens_terapeuticas" id="abordagensInput" value="">
      <input type="hidden" name="anos_experiencia" id="anosExperienciaInput" value="">

      <!-- PASSO: Escolha do perfil -->
      <div class="wizard-step" data-tipo="ambos">
        <p class="wizard-passo-contador">Vamos começar</p>
        <h2 class="wizard-pergunta-titulo">Como te queres juntar à Lyrios?</h2>
        <p class="wizard-pergunta-sub">Escolhe a opção que descreve o que procuras.</p>
        <div class="wizard-role-cards">
          <div class="wizard-role-card" data-valor="paciente" onclick="escolherPapel('paciente')">
            <i class="fa-solid fa-heart"></i>
            <h3>Sou Paciente</h3>
            <p>Quero encontrar um psicólogo e marcar consultas.</p>
          </div>
          <div class="wizard-role-card" data-valor="psicologo" onclick="escolherPapel('psicologo')">
            <i class="fa-solid fa-user-doctor"></i>
            <h3>Sou Psicólogo</h3>
            <p>Quero atender pacientes através da plataforma.</p>
          </div>
        </div>
      </div>

      <!-- PASSO (paciente): motivo -->
      <div class="wizard-step" data-tipo="paciente">
        <p class="wizard-passo-contador">Sobre ti</p>
        <h2 class="wizard-pergunta-titulo">O que te traz à Lyrios hoje?</h2>
        <p class="wizard-pergunta-sub">Escolhe todas as opções que se aplicam. Isto ajuda-nos a perceber-te melhor (opcional).</p>
        <div class="chip-select" id="chipsMotivo">
          <?php foreach (['Ansiedade', 'Stress', 'Relacionamentos', 'Autoestima', 'Luto', 'Sono', 'Motivação', 'Outro'] as $m): ?>
            <div class="chip" data-valor="<?= escape($m) ?>" onclick="alternarChip(this)"><?= escape($m) ?></div>
          <?php endforeach; ?>
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (paciente): experiência prévia -->
      <div class="wizard-step" data-tipo="paciente">
        <p class="wizard-passo-contador">Sobre ti</p>
        <h2 class="wizard-pergunta-titulo">Já fizeste algum acompanhamento psicológico antes?</h2>
        <div class="opcoes-cartao">
          <div class="opcao-cartao" data-valor="1" onclick="escolherExperiencia(this,1)"><i class="fa-solid fa-check"></i>Sim</div>
          <div class="opcao-cartao" data-valor="0" onclick="escolherExperiencia(this,0)"><i class="fa-solid fa-xmark"></i>Não</div>
        </div>
        <div class="wizard-nav"><button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button></div>
      </div>

      <!-- PASSO (paciente): preferência de género -->
      <div class="wizard-step" data-tipo="paciente">
        <p class="wizard-passo-contador">Preferências</p>
        <h2 class="wizard-pergunta-titulo">Preferes um psicólogo de que género?</h2>
        <div class="opcoes-cartao col-3">
          <div class="opcao-cartao" data-valor="sem_preferencia" onclick="escolherPreferencia(this,'sem_preferencia')"><i class="fa-solid fa-shuffle"></i>Sem preferência</div>
          <div class="opcao-cartao" data-valor="feminino" onclick="escolherPreferencia(this,'feminino')"><i class="fa-solid fa-venus"></i>Feminino</div>
          <div class="opcao-cartao" data-valor="masculino" onclick="escolherPreferencia(this,'masculino')"><i class="fa-solid fa-mars"></i>Masculino</div>
        </div>
        <div class="wizard-nav"><button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button></div>
      </div>

      <!-- PASSO (psicólogo): especialidades -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Perfil profissional</p>
        <h2 class="wizard-pergunta-titulo">Quais são as tuas áreas de especialidade?</h2>
        <p class="wizard-pergunta-sub">Escolhe todas as que se aplicam.</p>
        <div class="chip-select" id="chipsEspecialidade">
          <?php foreach (['Ansiedade', 'Depressão', 'Terapia de Casal', 'Trauma', 'Luto', 'Adolescentes', 'Autoestima', 'Stress', 'Sono', 'Dependências'] as $e): ?>
            <div class="chip" data-valor="<?= escape($e) ?>" onclick="alternarChipEspecialidade(this)"><?= escape($e) ?></div>
          <?php endforeach; ?>
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="avancarComEspecialidade()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (psicólogo): abordagem terapêutica -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Perfil profissional</p>
        <h2 class="wizard-pergunta-titulo">Que abordagens terapêuticas usas?</h2>
        <p class="wizard-pergunta-sub">Escolhe todas as que se aplicam (opcional).</p>
        <div class="chip-select" id="chipsAbordagem">
          <?php foreach (['Terapia Cognitivo-Comportamental', 'Psicanálise', 'Terapia Sistémica', 'Humanista', 'Gestalt-Terapia', 'Terapia Breve'] as $a): ?>
            <div class="chip" data-valor="<?= escape($a) ?>" onclick="alternarChipAbordagem(this)"><?= escape($a) ?></div>
          <?php endforeach; ?>
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary btn-seguinte" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (psicólogo): anos de experiência -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Perfil profissional</p>
        <h2 class="wizard-pergunta-titulo">Há quantos anos exerces psicologia?</h2>
        <div class="opcoes-cartao col-4">
          <div class="opcao-cartao" data-valor="0-2" onclick="escolherExperienciaAnos(this,'0-2')">0-2 anos</div>
          <div class="opcao-cartao" data-valor="3-5" onclick="escolherExperienciaAnos(this,'3-5')">3-5 anos</div>
          <div class="opcao-cartao" data-valor="6-10" onclick="escolherExperienciaAnos(this,'6-10')">6-10 anos</div>
          <div class="opcao-cartao" data-valor="10+" onclick="escolherExperienciaAnos(this,'10+')">10+ anos</div>
        </div>
        <div class="wizard-nav"><button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button></div>
      </div>

      <!-- PASSO (psicólogo): preço por sessão -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Perfil profissional</p>
        <h2 class="wizard-pergunta-titulo">Quanto queres cobrar por sessão?</h2>
        <p class="wizard-pergunta-sub">Podes alterar este valor a qualquer momento no teu perfil.</p>
        <div class="form-group"><input type="number" name="preco_sessao" value="5000" min="0" placeholder="Preço em Kz"></div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (psicólogo): biografia -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Perfil profissional</p>
        <h2 class="wizard-pergunta-titulo">Fala-nos um pouco de ti</h2>
        <p class="wizard-pergunta-sub">Esta biografia vai aparecer no teu perfil público (opcional).</p>
        <div class="form-group"><textarea name="biografia" placeholder="Conta a tua experiência, forma de trabalhar, o que os pacientes podem esperar..."></textarea></div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (psicólogo): certificado -->
      <div class="wizard-step" data-tipo="psicologo">
        <p class="wizard-passo-contador">Verificação profissional</p>
        <h2 class="wizard-pergunta-titulo">Anexa o teu documento de qualificação</h2>
        <p class="wizard-pergunta-sub">Diploma, cédula profissional ou equivalente (PDF, JPG ou PNG). Um administrador vai rever antes da tua conta ficar ativa.</p>
        <div class="form-group"><input type="file" name="certificado" accept="application/pdf,image/png,image/jpeg" required></div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSOS EXTRA: perguntas definidas pelo administrador -->
      <?php foreach ($perguntasPorTipo['paciente'] as $pExtra) { echo renderPerguntaExtra($pExtra); } ?>
      <?php foreach ($perguntasPorTipo['psicologo'] as $pExtra) { echo renderPerguntaExtra($pExtra); } ?>

      <!-- PASSO (ambos): dados pessoais -->
      <div class="wizard-step" data-tipo="ambos">
        <p class="wizard-passo-contador">Quase lá</p>
        <h2 class="wizard-pergunta-titulo">Os teus dados</h2>
        <div class="form-group"><label>Nome completo</label><input type="text" name="nome" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Telefone</label><input type="text" name="telefone"></div>
        <div class="form-group"><label>Data de nascimento</label><input type="date" name="data_nascimento" max="<?= date('Y-m-d', strtotime('-16 years')) ?>" required></div>
        <small style="color:#697871;display:block;margin-top:-12px;margin-bottom:16px;">É necessário ter pelo menos 16 anos para criar conta.</small>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (ambos): foto -->
      <div class="wizard-step" data-tipo="ambos">
        <p class="wizard-passo-contador">Quase lá</p>
        <h2 class="wizard-pergunta-titulo">Adiciona uma foto de perfil</h2>
        <p class="wizard-pergunta-sub">Opcional, mas ajuda a criar confiança. Podes adicionar mais tarde.</p>
        <div class="form-group"><input type="file" name="foto" accept="image/png,image/jpeg,image/webp"></div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="button" class="btn btn-primary" onclick="avancar()">Continuar</button>
        </div>
      </div>

      <!-- PASSO (ambos): password / submit -->
      <div class="wizard-step" data-tipo="ambos">
        <p class="wizard-passo-contador">Último passo</p>
        <h2 class="wizard-pergunta-titulo">Cria a tua password</h2>
        <div class="form-group">
          <input type="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres, com letras e números">
        </div>
        <div class="wizard-nav">
          <button type="button" class="btn-voltar" onclick="voltar()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
          <button type="submit" class="btn btn-primary btn-seguinte">Criar a minha conta</button>
        </div>
      </div>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:14px;">Já tens conta? <a href="<?= BASE_URL ?>/auth/login.php" style="color:#1f6f5c;font-weight:600;">Entrar</a></p>
  </div>
</div>

<script>
(function(){
  var passoAtual = 0;
  var tipoSelecionado = <?= json_encode($tipoInicial) ?> || null;
  var motivosSelecionados = [];
  var especialidadesSelecionadas = [];
  var abordagensSelecionadas = [];

  document.getElementById('tipoInput').value = tipoSelecionado || '';

  function passosAtivos(){
    return Array.prototype.filter.call(document.querySelectorAll('.wizard-step'), function(el){
      var t = el.getAttribute('data-tipo');
      return t === 'ambos' || (tipoSelecionado && t === tipoSelecionado);
    });
  }

  function mostrarErro(msg){
    var el = document.getElementById('erroWizard');
    el.textContent = msg;
    el.classList.add('visivel');
  }
  function limparErro(){
    var el = document.getElementById('erroWizard');
    el.textContent = '';
    el.classList.remove('visivel');
  }

  function render(){
    var lista = passosAtivos();
    document.querySelectorAll('.wizard-step').forEach(function(el){ el.classList.remove('ativo'); });
    if (lista[passoAtual]) lista[passoAtual].classList.add('ativo');
    document.getElementById('barraProgresso').style.width = Math.round(((passoAtual + 1) / lista.length) * 100) + '%';
    window.scrollTo({top: document.querySelector('.wizard-box').offsetTop - 20, behavior: 'smooth'});
  }

  function validarPassoAtual(){
    var lista = passosAtivos();
    var passo = lista[passoAtual];
    if (!passo) return true;

    var campos = passo.querySelectorAll('input:not([type=hidden]), select, textarea');
    for (var i = 0; i < campos.length; i++) {
      if (!campos[i].checkValidity()) {
        mostrarErro(campos[i].validationMessage || 'Preenche este campo corretamente.');
        campos[i].focus();
        return false;
      }
    }
    limparErro();
    return true;
  }

  window.avancar = function(){
    if (!validarPassoAtual()) return;
    var lista = passosAtivos();
    if (passoAtual < lista.length - 1) { passoAtual++; render(); }
  };
  window.voltar = function(){
    limparErro();
    if (passoAtual > 0) { passoAtual--; render(); }
  };

  window.escolherPapel = function(valor){
    tipoSelecionado = valor;
    document.getElementById('tipoInput').value = valor;
    document.querySelectorAll('.wizard-role-card').forEach(function(c){ c.classList.toggle('selecionado', c.getAttribute('data-valor') === valor); });
    passoAtual++;
    render();
  };

  window.alternarChip = function(el){
    var v = el.getAttribute('data-valor');
    var idx = motivosSelecionados.indexOf(v);
    if (idx === -1) { motivosSelecionados.push(v); el.classList.add('selecionado'); }
    else { motivosSelecionados.splice(idx, 1); el.classList.remove('selecionado'); }
    document.getElementById('motivoProcuraInput').value = motivosSelecionados.join(', ');
  };

  window.escolherExperiencia = function(el, valor){
    var pai = el.closest('.opcoes-cartao');
    pai.querySelectorAll('.opcao-cartao').forEach(function(c){ c.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    document.getElementById('experienciaInput').value = valor;
    setTimeout(window.avancar, 250);
  };

  window.escolherPreferencia = function(el, valor){
    var pai = el.closest('.opcoes-cartao');
    pai.querySelectorAll('.opcao-cartao').forEach(function(c){ c.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    document.getElementById('preferenciaInput').value = valor;
    setTimeout(window.avancar, 250);
  };

  window.alternarChipEspecialidade = function(el){
    var v = el.getAttribute('data-valor');
    var idx = especialidadesSelecionadas.indexOf(v);
    if (idx === -1) { especialidadesSelecionadas.push(v); el.classList.add('selecionado'); }
    else { especialidadesSelecionadas.splice(idx, 1); el.classList.remove('selecionado'); }
    document.getElementById('especialidadeInput').value = especialidadesSelecionadas.join(', ');
  };

  window.alternarChipAbordagem = function(el){
    var v = el.getAttribute('data-valor');
    var idx = abordagensSelecionadas.indexOf(v);
    if (idx === -1) { abordagensSelecionadas.push(v); el.classList.add('selecionado'); }
    else { abordagensSelecionadas.splice(idx, 1); el.classList.remove('selecionado'); }
    document.getElementById('abordagensInput').value = abordagensSelecionadas.join(', ');
  };

  window.escolherExperienciaAnos = function(el, valor){
    var pai = el.closest('.opcoes-cartao');
    pai.querySelectorAll('.opcao-cartao').forEach(function(c){ c.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    document.getElementById('anosExperienciaInput').value = valor;
    setTimeout(window.avancar, 250);
  };

  window.avancarComEspecialidade = function(){
    if (especialidadesSelecionadas.length === 0) {
      mostrarErro('Escolhe pelo menos uma área de especialidade.');
      return;
    }
    window.avancar();
  };

  window.alternarChipExtra = function(el, perguntaId){
    var campo = document.getElementById('input_extra_' + perguntaId);
    var lista = campo.value ? campo.value.split(', ') : [];
    var v = el.getAttribute('data-valor');
    var idx = lista.indexOf(v);
    if (idx === -1) { lista.push(v); el.classList.add('selecionado'); }
    else { lista.splice(idx, 1); el.classList.remove('selecionado'); }
    campo.value = lista.filter(Boolean).join(', ');
  };

  window.avancarChipExtra = function(perguntaId, obrigatorio){
    var campo = document.getElementById('input_extra_' + perguntaId);
    if (obrigatorio && !campo.value) {
      mostrarErro('Escolhe pelo menos uma opção.');
      return;
    }
    window.avancar();
  };

  window.escolherCartaoExtra = function(el, perguntaId, valor){
    var pai = el.closest('.opcoes-cartao');
    pai.querySelectorAll('.opcao-cartao').forEach(function(c){ c.classList.remove('selecionado'); });
    el.classList.add('selecionado');
    document.getElementById('input_extra_' + perguntaId).value = valor;
    setTimeout(window.avancar, 250);
  };

  document.getElementById('formRegisto').addEventListener('submit', function(e){
    if (!validarPassoAtual()) { e.preventDefault(); }
  });

  // Se o tipo já veio por URL (?tipo=paciente/psicologo), avança logo para o passo seguinte
  if (tipoSelecionado) {
    document.querySelectorAll('.wizard-role-card').forEach(function(c){ c.classList.toggle('selecionado', c.getAttribute('data-valor') === tipoSelecionado); });
    passoAtual = 1;
  }
  render();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
