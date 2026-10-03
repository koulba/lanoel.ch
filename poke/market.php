<?php
require_once __DIR__ . '/includes/bootstrap.php';
pokeRequireLogin();

$userId = (int)$_SESSION['user_id'];
$catalog = pokeCatalog();
$myQty = pokeUserQty($pdo, $userId);

// Pour chaque ami : ce qu'il a en double et qui me manque, et inversement
$matches = [];
foreach (pokeFriends($pdo, $userId) as $friend) {
    $forMe = [];
    $forThem = [];
    foreach ($catalog['cards'] as $id => $card) {
        $theirs = $friend['qty'][$id] ?? 0;
        $mine = $myQty[$id] ?? 0;
        if ($theirs >= 2 && $mine === 0) $forMe[] = $card;
        if ($mine >= 2 && $theirs === 0) $forThem[] = $card;
    }
    $matches[] = $friend + ['forMe' => $forMe, 'forThem' => $forThem];
}

// Les échanges "gagnant-gagnant" d'abord
usort($matches, function ($a, $b) {
    $sa = [min(count($a['forMe']), count($a['forThem'])), count($a['forMe']) + count($a['forThem'])];
    $sb = [min(count($b['forMe']), count($b['forThem'])), count($b['forMe']) + count($b['forThem'])];
    return $sb <=> $sa;
});

$myDoubles = count(array_filter($myQty, function ($q) { return $q >= 2; }));

$pageTitle = 'Bourse aux échanges';
$activeTab = 'market';
include __DIR__ . '/includes/layout_top.php';

function thumbs(array $cards, $max = 8) {
    $html = '';
    foreach (array_slice($cards, 0, $max) as $card) {
        $html .= '<img loading="lazy" referrerpolicy="no-referrer" src="' . h($card['img']) . '" alt="' . h($card['name']) . '" title="' . h($card['name']) . '">';
    }
    if (count($cards) > $max) {
        $html .= '<span class="more">+' . (count($cards) - $max) . '</span>';
    }
    return $html;
}
?>

<section>
    <h1 class="h1">Bourse</h1>
    <p class="muted">Les amis qui ont des doubles qui te manquent, et ceux à qui tes doubles feraient plaisir.</p>

    <?php if (empty($myQty)): ?>
        <div class="callout">Commence par remplir <a class="link" href="index.php">ta collection</a> pour voir les échanges possibles.</div>
    <?php elseif ($myDoubles === 0): ?>
        <div class="callout">Tu n'as encore aucun double à proposer. Tu peux quand même demander des cartes à tes amis.</div>
    <?php endif; ?>

    <?php if (empty($matches)): ?>
        <p class="empty">Personne d'autre n'a encore rempli sa collection. Partage le lien à tes potes !</p>
    <?php endif; ?>

    <div class="stack">
        <?php foreach ($matches as $m): ?>
            <article class="friend">
                <header>
                    <img class="avatar" src="<?= h(pokeAvatar($m['username'], $m['avatar'])) ?>" alt="">
                    <div>
                        <strong><?= h($m['username']) ?></strong>
                        <span class="muted small"><?= count($m['qty']) ?> cartes différentes</span>
                    </div>
                    <?php if ($m['forMe'] && $m['forThem']): ?>
                        <span class="tag good">Match</span>
                    <?php endif; ?>
                </header>

                <div class="row">
                    <h3><?= h($m['username']) ?> a pour toi <b><?= count($m['forMe']) ?></b></h3>
                    <div class="thumbs"><?= $m['forMe'] ? thumbs($m['forMe']) : '<span class="muted small">Rien qui te manque pour l\'instant</span>' ?></div>
                </div>
                <div class="row">
                    <h3>Tu as pour <?= h($m['username']) ?> <b><?= count($m['forThem']) ?></b></h3>
                    <div class="thumbs"><?= $m['forThem'] ? thumbs($m['forThem']) : '<span class="muted small">Aucun de tes doubles ne lui manque</span>' ?></div>
                </div>

                <a class="btn primary block" href="trade.php?with=<?= (int)$m['id'] ?>">Proposer un échange</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
