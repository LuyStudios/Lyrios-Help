<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('admin');
$titulo = "Cupões"; $pagina = 'cupoes'; $areaTipo = 'admin';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    if (isset($_POST['adicionar'])) {
        $codigo = strtoupper(trim($_POST['codigo']));
        $percentagem = (float)$_POST['percentagem_desconto'];
        $validade = validarData($_POST['validade'] ?? '') ?: null;
        $usosMax = $_POST['usos_maximos'] !== '' ? (int)$_POST['usos_maximos'] : null;

        if ($codigo !== '' && $percentagem > 0 && $percentagem <= 100) {
            $stmt = $pdo->prepare("INSERT INTO cupoes (codigo, percentagem_desconto, validade, usos_maximos) VALUES (?,?,?,?)");
            try {
                $stmt->execute([$codigo, $percentagem, $validade, $usosMax]);
                $sucesso = 'Cupão criado com sucesso.';
            } catch (Exception $e) {
                $sucesso = ''; $erro = 'Já existe um cupão com este código.';
            }
        }
    } elseif (isset($_POST['alternar'])) {
        $pdo->prepare("UPDATE cupoes SET ativo = NOT ativo WHERE id = ?")->execute([(int)$_POST['id']]);
    } elseif (isset($_POST['eliminar'])) {
        $pdo->prepare("DELETE FROM cupoes WHERE id = ?")->execute([(int)$_POST['id']]);
    }
}

$cupoes = $pdo->query("SELECT * FROM cupoes ORDER BY criado_em DESC")->fetchAll();
require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Cupões / Ofertas de Consulta</h1>
<?php if (!empty($erro)): ?><div class="alert alert-error"><?= escape($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:22px;">
  <h3>Criar novo cupão</h3>
  <form method="post" class="grid grid-4" style="align-items:end;">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Código</label><input type="text" name="codigo" placeholder="BEMVINDO10" required></div>
    <div class="form-group"><label>Desconto (%)</label><input type="number" name="percentagem_desconto" min="1" max="100" required></div>
    <div class="form-group"><label>Validade (opcional)</label><input type="date" name="validade"></div>
    <div class="form-group"><label>Usos máximos (opcional)</label><input type="number" name="usos_maximos" min="1"></div>
    <div class="form-group" style="grid-column:1/-1;"><button class="btn btn-primary" name="adicionar" type="submit">Criar cupão</button></div>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Código</th><th>Desconto</th><th>Validade</th><th>Usos</th><th>Estado</th><th>Ações</th></tr>
    <?php foreach ($cupoes as $c): ?>
    <tr>
      <td><strong><?= escape($c['codigo']) ?></strong></td>
      <td><?= $c['percentagem_desconto'] ?>%</td>
      <td><?= $c['validade'] ? date('d/m/Y', strtotime($c['validade'])) : 'Sem limite' ?></td>
      <td><?= $c['usos_atuais'] ?><?= $c['usos_maximos'] ? ' / ' . $c['usos_maximos'] : '' ?></td>
      <td><span class="badge badge-<?= $c['ativo'] ? 'confirmada' : 'cancelada' ?>"><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
      <td style="display:flex;gap:6px;">
        <form method="post"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button name="alternar" class="btn btn-small btn-outline" style="color:#1f6f5c;border:1px solid #1f6f5c;"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button></form>
        <form method="post" onsubmit="return confirm('Eliminar cupão?');"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button name="eliminar" class="btn btn-small btn-danger">Eliminar</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($cupoes)): ?><tr><td colspan="6">Nenhum cupão criado.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
