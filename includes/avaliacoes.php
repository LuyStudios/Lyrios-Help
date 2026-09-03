<?php
/** Devolve a média de avaliação e o total de avaliações de um psicólogo */
function mediaAvaliacoes($pdo, $psicologoId) {
    $stmt = $pdo->prepare("SELECT AVG(nota) media, COUNT(*) total FROM avaliacoes WHERE psicologo_id = ?");
    $stmt->execute([$psicologoId]);
    $r = $stmt->fetch();
    return ['media' => $r['media'] ? round((float)$r['media'], 1) : 0, 'total' => (int)$r['total']];
}

/** Gera o HTML de estrelas (cheia / meia / vazia) para uma média de 0 a 5 */
function estrelasHtml($media, $total = null) {
    $cheias = floor($media);
    $meia = ($media - $cheias) >= 0.5 ? 1 : 0;
    $vazias = 5 - $cheias - $meia;

    $html = '<span class="estrelas">';
    for ($i = 0; $i < $cheias; $i++) $html .= '<i class="fa-solid fa-star"></i>';
    if ($meia) $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    for ($i = 0; $i < $vazias; $i++) $html .= '<i class="fa-regular fa-star"></i>';
    $html .= '</span>';

    if ($total !== null) {
        $html .= ' <span class="estrelas-total">' . ($media > 0 ? number_format($media, 1) : 'Sem avaliações') . ($total > 0 ? " ($total)" : '') . '</span>';
    }
    return $html;
}
