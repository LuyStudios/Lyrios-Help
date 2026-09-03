<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('admin');
$titulo = "Certificados"; $pagina = 'documentos'; $areaTipo = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $id = (int)$_POST['id'];
    if (isset($_POST['aprovar'])) {
        $pdo->prepare("UPDATE certificados SET estado='aprovado', motivo_rejeicao=NULL, revisto_em=NOW() WHERE id=?")->execute([$id]);
        $stmt = $pdo->prepare("SELECT psicologo_id FROM certificados WHERE id=?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
        if ($c) registarAtividade($pdo, $c['psicologo_id'], 'Um dos teus documentos foi aprovado pelo administrador.');
    } elseif (isset($_POST['rejeitar'])) {
        $motivo = trim($_POST['motivo']) ?: 'Documento não aceite. Envia um documento válido.';
        $pdo->prepare("UPDATE certificados SET estado='rejeitado', motivo_rejeicao=?, revisto_em=NOW() WHERE id=?")->execute([$motivo, $id]);
        $stmt = $pdo->prepare("SELECT psicologo_id FROM certificados WHERE id=?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
        if ($c) registarAtividade($pdo, $c['psicologo_id'], 'Um dos teus documentos foi rejeitado: ' . $motivo);
    }
}

$filtroEstado = $_GET['estado'] ?? '';
$sql = "SELECT c.*, u.nome AS nome_psicologo, u.email FROM certificados c JOIN utilizadores u ON u.id = c.psicologo_id";
$params = [];
if ($filtroEstado) { $sql .= " WHERE c.estado = ?"; $params[] = $filtroEstado; }
$sql .= " ORDER BY c.enviado_em DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Certificados de Psicólogos</h1>
<div style="margin-bottom:16px;">
  <a href="?" class="btn btn-small <?= $filtroEstado==''?'btn-primary':'btn-outline' ?>" style="color:#1f6f5c;border:1px solid #1f6f5c;">Todos</a>
  <a href="?estado=pendente" class="btn btn-small <?= $filtroEstado=='pendente'?'btn-primary':'btn-outline' ?>" style="color:#1f6f5c;border:1px solid #1f6f5c;">Pendentes</a>
  <a href="?estado=aprovado" class="btn btn-small <?= $filtroEstado=='aprovado'?'btn-primary':'btn-outline' ?>" style="color:#1f6f5c;border:1px solid #1f6f5c;">Aprovados</a>
  <a href="?estado=rejeitado" class="btn btn-small <?= $filtroEstado=='rejeitado'?'btn-primary':'btn-outline' ?>" style="color:#1f6f5c;border:1px solid #1f6f5c;">Rejeitados</a>
</div>
<div class="card">
  <table>
    <tr><th>Psicólogo</th><th>Documento</th><th>Estado</th><th>Data</th><th>Ações</th></tr>
    <?php foreach ($certificados as $c): ?>
    <tr>
      <td><?= escape($c['nome_psicologo']) ?><br><small style="color:#697871;"><?= escape($c['email']) ?></small></td>
      <td><a href="<?= BASE_URL ?>/documentos/ver.php?id=<?= $c['id'] ?>" target="_blank" style="color:#1f6f5c;font-weight:600;"><i class="fa-solid fa-file"></i> <?= escape($c['nome_original']) ?></a></td>
      <td>
        <span class="badge badge-<?= $c['estado']==='aprovado'?'confirmada':($c['estado']==='rejeitado'?'cancelada':'pendente') ?>"><?= ucfirst($c['estado']) ?></span>
        <?php if ($c['motivo_rejeicao']): ?><div style="font-size:12px;color:#b8503f;margin-top:4px;"><?= escape($c['motivo_rejeicao']) ?></div><?php endif; ?>
      </td>
      <td><?= date('d/m/Y H:i', strtotime($c['enviado_em'])) ?></td>
      <td style="display:flex;gap:6px;flex-wrap:wrap;">
        <?php if ($c['estado'] !== 'aprovado'): ?>
        <form method="post"><?php csrfCampo(); ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button name="aprovar" class="btn btn-small btn-primary">Aprovar</button></form>
        <?php endif; ?>
        <?php if ($c['estado'] !== 'rejeitado'): ?>
        <form method="post" onsubmit="var m=prompt('Motivo da rejeição:'); if(m===null) return false; this.motivo.value=m;">
          <?php csrfCampo(); ?>
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="motivo" value="">
          <button name="rejeitar" class="btn btn-small btn-danger">Rejeitar</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($certificados)): ?><tr><td colspan="5">Nenhum documento encontrado.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
