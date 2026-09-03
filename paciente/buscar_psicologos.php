<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirTipo('paciente');
$titulo = "Procurar Psicólogos"; $pagina = 'buscar'; $areaTipo = 'paciente';

$especialidadesComuns = ['Ansiedade', 'Depressão', 'Terapia de Casal', 'Trauma', 'Luto', 'Adolescentes', 'Autoestima', 'Stress', 'Sono', 'Dependências'];

// Busca protegida por prepared statement (nunca concatena o texto pesquisado no SQL)
$termo = trim($_GET['q'] ?? '');
$filtroEspecialidade = in_array($_GET['especialidade'] ?? '', $especialidadesComuns, true) ? $_GET['especialidade'] : '';
$ordenar = validarLista($_GET['ordenar'] ?? 'relevancia', ['relevancia', 'avaliacao', 'preco_asc', 'preco_desc'], 'relevancia');

$sql = "SELECT u.id, u.nome, u.foto, p.especialidade, p.abordagens_terapeuticas, p.anos_experiencia, p.biografia, p.preco_sessao, p.status_personalizado,
        (SELECT COUNT(*) FROM certificados c WHERE c.psicologo_id = u.id AND c.estado='aprovado') AS verificado
        FROM utilizadores u JOIN perfis_psicologos p ON p.utilizador_id = u.id
        WHERE u.tipo='psicologo' AND u.estado='ativo' AND p.aprovado = 1";
$params = [];
if ($termo !== '') {
    $sql .= " AND (u.nome LIKE ? OR p.especialidade LIKE ?)";
    $params[] = '%' . $termo . '%';
    $params[] = '%' . $termo . '%';
}
if ($filtroEspecialidade !== '') {
    $sql .= " AND p.especialidade LIKE ?";
    $params[] = '%' . $filtroEspecialidade . '%';
}
$sql .= " ORDER BY u.nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$psicologos = $stmt->fetchAll();

foreach ($psicologos as &$p) {
    $p['avaliacao'] = mediaAvaliacoes($pdo, $p['id']);
}
unset($p);

// Ordenação final (feita em PHP porque a avaliação é calculada após a consulta)
switch ($ordenar) {
    case 'avaliacao':
        usort($psicologos, function ($a, $b) { return $b['avaliacao']['media'] <=> $a['avaliacao']['media']; });
        break;
    case 'preco_asc':
        usort($psicologos, function ($a, $b) { return $a['preco_sessao'] <=> $b['preco_sessao']; });
        break;
    case 'preco_desc':
        usort($psicologos, function ($a, $b) { return $b['preco_sessao'] <=> $a['preco_sessao']; });
        break;
}

require_once __DIR__ . '/../includes/dash_header.php';
?>
<h1>Procurar Psicólogos</h1>

<form method="get" class="busca-barra">
  <input type="text" name="q" placeholder="Procurar por nome ou especialidade..." value="<?= escape($termo) ?>">
  <input type="hidden" name="especialidade" value="<?= escape($filtroEspecialidade) ?>">
  <select name="ordenar" onchange="this.form.submit()" style="border:1.5px solid var(--line);border-radius:999px;padding:0 18px;font-family:inherit;font-size:14px;">
    <option value="relevancia" <?= $ordenar === 'relevancia' ? 'selected' : '' ?>>Mais relevantes</option>
    <option value="avaliacao" <?= $ordenar === 'avaliacao' ? 'selected' : '' ?>>Melhor avaliados</option>
    <option value="preco_asc" <?= $ordenar === 'preco_asc' ? 'selected' : '' ?>>Preço: mais baixo</option>
    <option value="preco_desc" <?= $ordenar === 'preco_desc' ? 'selected' : '' ?>>Preço: mais alto</option>
  </select>
  <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Procurar</button>
</form>

<div class="chip-select" style="margin-bottom:32px;">
  <a href="?q=<?= urlencode($termo) ?>&ordenar=<?= $ordenar ?>" class="chip <?= $filtroEspecialidade === '' ? 'selecionado' : '' ?>">Todas as áreas</a>
  <?php foreach ($especialidadesComuns as $esp): ?>
    <a href="?q=<?= urlencode($termo) ?>&especialidade=<?= urlencode($esp) ?>&ordenar=<?= $ordenar ?>" class="chip <?= $filtroEspecialidade === $esp ? 'selecionado' : '' ?>"><?= escape($esp) ?></a>
  <?php endforeach; ?>
</div>

<div class="grid grid-3">
  <?php foreach ($psicologos as $i => $p): ?>
  <div class="psicologo-card reveal reveal-atraso-<?= min(($i % 3) + 1, 3) ?>">
    <div class="topo">
      <?php if ($p['foto']): ?>
        <img src="<?= BASE_URL ?>/<?= escape($p['foto']) ?>" class="psicologo-foto">
      <?php else: ?>
        <div class="psicologo-foto-placeholder"><i class="fa-solid fa-user"></i></div>
      <?php endif; ?>
      <div>
        <h3 style="margin:0;font-size:17px;"><?= escape($p['nome']) ?></h3>
        <div style="color:var(--muted);font-size:13.5px;"><?= escape($p['especialidade'] ?: 'Psicólogo(a)') ?></div>
        <?php if ($p['verificado'] > 0): ?><span class="selo-verificado"><i class="fa-solid fa-shield-check"></i> Verificado</span><?php endif; ?>
        <?php if ($p['status_personalizado']): ?><div style="font-size:12px;color:var(--muted);margin-top:2px;"><i class="fa-solid fa-circle" style="font-size:6px;color:var(--success);"></i> <?= escape($p['status_personalizado']) ?></div><?php endif; ?>
      </div>
    </div>
    <div><?= estrelasHtml($p['avaliacao']['media'], $p['avaliacao']['total']) ?></div>
    <?php if ($p['anos_experiencia']): ?><div style="font-size:12.5px;color:var(--muted);"><i class="fa-solid fa-briefcase"></i> <?= escape($p['anos_experiencia']) ?> anos de experiência</div><?php endif; ?>
    <?php if ($p['abordagens_terapeuticas']): ?><div style="font-size:12.5px;color:var(--muted);"><i class="fa-solid fa-brain"></i> <?= escape(mb_strimwidth($p['abordagens_terapeuticas'], 0, 70, '...')) ?></div><?php endif; ?>
    <?php if ($p['biografia']): ?><p style="font-size:13.5px;color:var(--muted);margin:0;"><?= escape(mb_strimwidth($p['biografia'], 0, 120, '...')) ?></p><?php endif; ?>
    <p style="margin:0;font-weight:700;color:var(--primary-dark);font-family:'Fraunces',serif;"><?= formatarKz($p['preco_sessao']) ?> <span style="font-weight:500;color:var(--muted);font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;">/ sessão</span></p>
    <a href="<?= BASE_URL ?>/paciente/marcar_consulta.php?psicologo_id=<?= $p['id'] ?>" class="btn btn-primary btn-full">Marcar consulta</a>
  </div>
  <?php endforeach; ?>
  <?php if (empty($psicologos)): ?>
    <p style="color:var(--muted);">Nenhum psicólogo encontrado<?= $termo !== '' ? ' para "' . escape($termo) . '"' : '' ?>. Tenta outro termo ou remove os filtros.</p>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
