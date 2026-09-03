<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/upload.php';
exigirTipo('psicologo');
$titulo = "Meu Perfil"; $pagina = 'perfil'; $areaTipo = 'psicologo';
$uid = $_SESSION['utilizador_id'];
$sucesso = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    if (isset($_POST['guardar_perfil'])) {
        $nome = trim($_POST['nome']);
        $telefone = trim($_POST['telefone']);
        $especialidade = trim($_POST['especialidade']);
        $abordagens = trim($_POST['abordagens_terapeuticas'] ?? '');
        $anosExperiencia = validarLista($_POST['anos_experiencia'] ?? '', ['', '0-2', '3-5', '6-10', '10+'], '');
        $bio = trim($_POST['biografia']);
        $preco = (float)$_POST['preco_sessao'];
        $status = trim($_POST['status_personalizado'] ?? '');

        $pdo->prepare("UPDATE utilizadores SET nome=?, telefone=? WHERE id=?")->execute([$nome, $telefone, $uid]);
        $pdo->prepare("UPDATE perfis_psicologos SET especialidade=?, abordagens_terapeuticas=?, anos_experiencia=?, biografia=?, preco_sessao=?, status_personalizado=? WHERE utilizador_id=?")
            ->execute([$especialidade, $abordagens, $anosExperiencia ?: null, $bio, $preco, mb_substr($status, 0, 100), $uid]);
        $_SESSION['nome'] = $nome;
        registarAtividade($pdo, $uid, 'Atualizou os dados do perfil profissional.');
        $sucesso = 'Perfil atualizado com sucesso.';
    }

    if (isset($_POST['enviar_foto'])) {
        $res = uploadFoto('foto');
        if ($res['erro']) {
            $erro = $res['erro'];
        } elseif ($res['sucesso']) {
            $pdo->prepare("UPDATE utilizadores SET foto=? WHERE id=?")->execute([$res['caminho'], $uid]);
            registarAtividade($pdo, $uid, 'Atualizou a foto de perfil.');
            $sucesso = 'Foto de perfil atualizada com sucesso.';
        }
    }

    if (isset($_POST['enviar_certificado'])) {
        $res = uploadCertificado('certificado');
        if ($res['erro']) {
            $erro = $res['erro'];
        } elseif ($res['sucesso']) {
            $stmt = $pdo->prepare("INSERT INTO certificados (psicologo_id, nome_original, caminho, tipo, estado) VALUES (?,?,?,?,'pendente')");
            $stmt->execute([$uid, $res['nome_original'], $res['caminho'], $res['tipo']]);
            registarAtividade($pdo, $uid, 'Enviou um novo documento/certificado para verificação.');
            $sucesso = 'Documento enviado com sucesso. Aguarda a revisão do administrador.';
        }
    }
}

$stmt = $pdo->prepare("SELECT u.*, p.especialidade, p.abordagens_terapeuticas, p.anos_experiencia, p.biografia, p.preco_sessao, p.status_personalizado, p.aprovado FROM utilizadores u JOIN perfis_psicologos p ON p.utilizador_id=u.id WHERE u.id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$certificados = $pdo->prepare("SELECT * FROM certificados WHERE psicologo_id = ? ORDER BY enviado_em DESC");
$certificados->execute([$uid]);
$certificados = $certificados->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Meu Perfil</h1>
<?php if (!$user['aprovado']): ?><div class="alert alert-error">A tua conta ainda não foi aprovada pelo administrador.</div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;">

  <div class="card">
    <h3>Foto de Perfil</h3>
    <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px;">
      <?php if ($user['foto']): ?>
        <img src="<?= BASE_URL ?>/<?= escape($user['foto']) ?>" alt="Foto de perfil" style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:2px solid #e7ebe8;">
      <?php else: ?>
        <div style="width:84px;height:84px;border-radius:50%;background:#e8f3ef;display:flex;align-items:center;justify-content:center;color:#1f6f5c;font-size:28px;"><i class="fa-solid fa-user"></i></div>
      <?php endif; ?>
      <p style="color:#697871;font-size:13.5px;margin:0;">Uma foto profissional ajuda os pacientes a reconhecerem-te e aumenta a confiança na tua conta.</p>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?php csrfCampo(); ?>
      <div class="form-group"><input type="file" name="foto" accept="image/png,image/jpeg,image/webp" required></div>
      <button class="btn btn-primary btn-full" type="submit" name="enviar_foto">Enviar foto</button>
    </form>
  </div>

  <div class="card">
    <h3>Certificados e Documentos</h3>
    <p style="color:#697871;font-size:13.5px;">Envia o teu diploma, cédula profissional ou outro documento que comprove a tua qualificação. Um administrador vai rever e aprovar cada documento.</p>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:18px;">
      <?php csrfCampo(); ?>
      <div class="form-group"><input type="file" name="certificado" accept="application/pdf,image/png,image/jpeg" required></div>
      <button class="btn btn-primary btn-full" type="submit" name="enviar_certificado">Enviar documento</button>
    </form>
    <?php if (empty($certificados)): ?>
      <p style="color:#697871;font-size:13.5px;">Ainda não enviaste nenhum documento.</p>
    <?php else: ?>
      <table>
        <tr><th>Documento</th><th>Estado</th><th>Data</th></tr>
        <?php foreach ($certificados as $c): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/documentos/ver.php?id=<?= $c['id'] ?>" target="_blank" style="color:#1f6f5c;font-weight:600;"><?= escape($c['nome_original']) ?></a></td>
          <td>
            <span class="badge badge-<?= $c['estado']==='aprovado'?'confirmada':($c['estado']==='rejeitado'?'cancelada':'pendente') ?>"><?= ucfirst($c['estado']) ?></span>
            <?php if ($c['estado']==='rejeitado' && $c['motivo_rejeicao']): ?>
              <div style="font-size:12px;color:#b8503f;margin-top:4px;"><?= escape($c['motivo_rejeicao']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= date('d/m/Y', strtotime($c['enviado_em'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

</div>

<div class="card" style="margin-top:24px;">
  <h3>Dados do Perfil</h3>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Nome</label><input type="text" name="nome" value="<?= escape($user['nome']) ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" value="<?= escape($user['email']) ?>" disabled></div>
    <div class="form-group"><label>Telefone</label><input type="text" name="telefone" value="<?= escape($user['telefone']) ?>"></div>
    <div class="form-group"><label>Especialidade</label><input type="text" name="especialidade" value="<?= escape($user['especialidade']) ?>"></div>
    <div class="form-group"><label>Abordagens terapêuticas</label><input type="text" name="abordagens_terapeuticas" value="<?= escape($user['abordagens_terapeuticas']) ?>" placeholder="Ex: Terapia Cognitivo-Comportamental, Humanista..."></div>
    <div class="form-group">
      <label>Anos de experiência</label>
      <select name="anos_experiencia">
        <option value="" <?= !$user['anos_experiencia'] ? 'selected' : '' ?>>Não indicado</option>
        <?php foreach (['0-2', '3-5', '6-10', '10+'] as $faixa): ?>
          <option value="<?= $faixa ?>" <?= $user['anos_experiencia'] === $faixa ? 'selected' : '' ?>><?= $faixa ?> anos</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Biografia</label><textarea name="biografia"><?= escape($user['biografia']) ?></textarea></div>
    <div class="form-group"><label>Preço por sessão (Kz)</label><input type="number" name="preco_sessao" value="<?= escape($user['preco_sessao']) ?>"></div>
    <div class="form-group">
      <label>Status atual (visível aos pacientes)</label>
      <input type="text" name="status_personalizado" value="<?= escape($user['status_personalizado']) ?>" placeholder="Ex: Disponível esta semana, Em pausa até dia 20...">
    </div>
    <button class="btn btn-primary" type="submit" name="guardar_perfil">Guardar alterações</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
