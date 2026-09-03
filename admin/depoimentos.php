<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/upload.php';
exigirTipo('admin');
$titulo = "Depoimentos"; $pagina = 'depoimentos'; $areaTipo = 'admin';
$sucesso = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    if (isset($_POST['adicionar'])) {
        $nome = trim($_POST['nome_paciente']);
        $texto = trim($_POST['texto']);
        $fotoUrlManual = trim($_POST['foto_url'] ?? '');

        if ($nome !== '' && $texto !== '') {
            $fotoFinal = $fotoUrlManual !== '' ? $fotoUrlManual : null;

            $resFoto = uploadFoto('foto');
            if ($resFoto['erro']) {
                $erro = $resFoto['erro'];
            } else {
                if ($resFoto['sucesso']) $fotoFinal = $resFoto['caminho'];
                $pdo->prepare("INSERT INTO depoimentos (nome_paciente, texto, foto_url) VALUES (?,?,?)")
                    ->execute([$nome, mb_substr($texto, 0, 600), $fotoFinal]);
                $sucesso = 'Depoimento adicionado com sucesso.';
            }
        }
    } elseif (isset($_POST['alternar'])) {
        $pdo->prepare("UPDATE depoimentos SET ativo = NOT ativo WHERE id = ?")->execute([(int)$_POST['id']]);
    } elseif (isset($_POST['eliminar'])) {
        $pdo->prepare("DELETE FROM depoimentos WHERE id = ?")->execute([(int)$_POST['id']]);
    }
}

$depoimentos = $pdo->query("SELECT * FROM depoimentos ORDER BY criado_em DESC")->fetchAll();
require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Depoimentos</h1>
<p style="color:#697871;">Aparecem na página inicial e na página "A Nossa História", num carrossel com fotos. Usa depoimentos e fotos reais dos teus próprios pacientes (com consentimento deles) sempre que possível — isso passa muito mais credibilidade do que fotos genéricas.</p>
<?php if ($erro): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
<div class="card" style="margin-bottom:22px;">
  <h3>Adicionar depoimento</h3>
  <form method="post" class="grid grid-2" enctype="multipart/form-data">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Nome do paciente</label><input type="text" name="nome_paciente" required></div>
    <div class="form-group"><label>Foto (opcional)</label><input type="file" name="foto" accept="image/png,image/jpeg,image/webp"></div>
    <div class="form-group" style="grid-column:1/-1;"><label>Ou URL de uma foto (opcional, se não fizeres upload)</label><input type="text" name="foto_url" placeholder="https://..."></div>
    <div class="form-group" style="grid-column:1/-1;"><label>Depoimento</label><textarea name="texto" maxlength="600" required></textarea></div>
    <div class="form-group"><button class="btn btn-primary" name="adicionar" type="submit">Adicionar</button></div>
  </form>
</div>
<div class="card">
  <table>
    <tr><th>Foto</th><th>Nome</th><th>Depoimento</th><th>Estado</th><th>Ações</th></tr>
    <?php foreach ($depoimentos as $d): ?>
    <tr>
      <td>
        <?php if ($d['foto_url']): ?>
          <img src="<?= escape(urlFoto($d['foto_url'])) ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">
        <?php else: ?>-<?php endif; ?>
      </td>
      <td><?= escape($d['nome_paciente']) ?></td>
      <td><?= escape($d['texto']) ?></td>
      <td><span class="badge badge-<?= $d['ativo'] ? 'confirmada' : 'cancelada' ?>"><?= $d['ativo'] ? 'Visível' : 'Oculto' ?></span></td>
      <td style="display:flex;gap:6px;">
        <form method="post"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><button name="alternar" class="btn btn-small btn-outline" style="color:#1f6f5c;border:1px solid #1f6f5c;"><?= $d['ativo'] ? 'Ocultar' : 'Mostrar' ?></button></form>
        <form method="post" onsubmit="return confirm('Eliminar?');"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><button name="eliminar" class="btn btn-small btn-danger">Eliminar</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
