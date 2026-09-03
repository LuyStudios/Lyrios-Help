<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Meu Perfil"; $pagina = 'perfil'; $areaTipo = 'paciente';
$uid = $_SESSION['utilizador_id'];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $stmt = $pdo->prepare("UPDATE utilizadores SET nome=?, telefone=? WHERE id=?");
    $stmt->execute([$nome, $telefone, $uid]);
    $_SESSION['nome'] = $nome;
    registarAtividade($pdo, $uid, 'Atualizou os dados do perfil.');
    $sucesso = 'Perfil atualizado com sucesso.';

    if (!empty($_POST['nova_password'])) {
        $hash = password_hash($_POST['nova_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE utilizadores SET password=? WHERE id=?")->execute([$hash, $uid]);
        $sucesso .= ' Password alterada.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Meu Perfil</h1>
<div class="card" style="max-width:500px;">
  <?php if ($sucesso): ?><div class="alert alert-success"><?= escape($sucesso) ?></div><?php endif; ?>
  <form method="post">
    <?php csrfCampo(); ?>
    <div class="form-group"><label>Nome</label><input type="text" name="nome" value="<?= escape($user['nome']) ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" value="<?= escape($user['email']) ?>" disabled></div>
    <div class="form-group"><label>Telefone</label><input type="text" name="telefone" value="<?= escape($user['telefone']) ?>"></div>
    <div class="form-group"><label>Nova Password (opcional)</label><input type="password" name="nova_password" placeholder="Deixa vazio para não alterar"></div>
    <button class="btn btn-primary btn-full" type="submit">Guardar alterações</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
