<?php
/**
 * Proposer un échange à un ami : je choisis ce que je donne (mes doubles)
 * et ce que je reçois (ses doubles).
 */
require_once __DIR__ . '/includes/bootstrap.php';
pokeRequireLogin();

$userId = (int)$_SESSION['user_id'];
$friendId = (int)($_GET['with'] ?? $_POST['with'] ?? 0);

$stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
$stmt->execute([$friendId]);
$friend = $stmt->fetch();
if (!$friend || $friendId === $userId) {
    pokeFlash("Ami introuvable.");
    redirect('market.php');
}

$catalog = pokeCatalog();
$myQty = pokeUserQty($pdo, $userId);
$theirQty = pokeUserQty($pdo, $friendId);

/** Doubles d'un joueur : [card_id => nb échangeables], ceux qui manquent à l'autre en premier */
function spares(array $owner, array $other, array $cards) {
    $first = [];
    $rest = [];
    foreach ($cards as $id => $card) {
        $qty = $owner[$id] ?? 0;
        if ($qty < 2) continue;
        if (empty($other[$id])) $first[$id] = $qty - 1;
        else $rest[$id] = $qty - 1;
    }
    return $first + $rest;
}

$mySpares = spares($myQty, $theirQty, $catalog['cards']);
$theirSpares = spares($theirQty, $myQty, $catalog['cards']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pokeCheckCsrf();
    $give = [];
    $get = [];
    foreach ((array)($_POST['give'] ?? []) as $id => $n) {
        $n = (int)$n;
        if ($n > 0 && isset($mySpares[$id])) $give[$id] = min($n, $mySpares[$id]);
    }
    foreach ((array)($_POST['get'] ?? []) as $id => $n) {
        $n = (int)$n;
        if ($n > 0 && isset($theirSpares[$id])) $get[$id] = min($n, $theirSpares[$id]);
    }
    $message = mb_substr(trim($_POST['message'] ?? ''), 0, 500);

    if (!$give && !$get) {
        $error = "Choisis au moins une carte.";
    } else {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO poke_trades (from_user, to_user, message) VALUES (?, ?, ?)")
            ->execute([$userId, $friendId, $message !== '' ? $message : null]);
        $tradeId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO poke_trade_items (trade_id, card_id, side, qty) VALUES (?, ?, ?, ?)");
        foreach ($give as $id => $n) $ins->execute([$tradeId, $id, 'give', $n]);
        foreach ($get as $id => $n) $ins->execute([$tradeId, $id, 'get', $n]);
        $pdo->commit();

        pokeFlash("Proposition envoyée à " . $friend['username'] . " !");
        redirect('trades.php');
    }
}

$pageTitle = 'Échange avec ' . $friend['username'];
$activeTab = 'market';
include __DIR__ . '/includes/layout_top.php';

function pickList($side, array $spares, array $otherQty, $flagLabel) {
    if (!$spares) {
        return '<p class="muted small">Aucun double disponible.</p>';
    }
    $html = '<div class="pick">';
    foreach ($spares as $id => $max) {
        $card = pokeCard($id);
        $missing = empty($otherQty[$id]);
        $html .= '<button type="button" class="pick-card' . ($missing ? ' wanted' : '') . '" data-side="' . $side . '" data-id="' . h($id) . '" data-max="' . (int)$max . '">'
            . '<img loading="lazy" referrerpolicy="no-referrer" src="' . h($card['img']) . '" alt="">'
            . '<span class="pick-name">' . h($card['name']) . '</span>'
            . ($missing ? '<span class="pick-flag">' . h($flagLabel) . '</span>' : '')
            . ($max > 1 ? '<span class="pick-max">' . (int)$max . ' dispo</span>' : '')
            . '<span class="pick-count" hidden></span>'
            . '<input type="hidden" name="' . $side . '[' . h($id) . ']" value="0">'
            . '</button>';
    }
    return $html . '</div>';
}
?>

<section>
    <a href="market.php" class="back">← Bourse</a>
    <div class="trade-head">
        <img class="avatar" src="<?= h(pokeAvatar($friend['username'], $friend['avatar'])) ?>" alt="">
        <h1 class="h1">Échange avec <?= h($friend['username']) ?></h1>
    </div>
    <p class="muted small">Touche une carte pour la sélectionner (re-touche pour en ajouter ou retirer). Les cartes surlignées manquent à l'autre.</p>

    <?php if (isset($error)): ?>
        <div class="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="tradeForm">
        <input type="hidden" name="csrf" value="<?= h(pokeCsrfToken()) ?>">
        <input type="hidden" name="with" value="<?= (int)$friendId ?>">

        <h2 class="h2">Tu reçois <span class="count" data-total="get">0</span></h2>
        <?= pickList('get', $theirSpares, $myQty, 'Te manque') ?>

        <h2 class="h2">Tu donnes <span class="count" data-total="give">0</span></h2>
        <?= pickList('give', $mySpares, $theirQty, 'Manque à ' . $friend['username']) ?>

        <label class="form">Message (facultatif)
            <textarea name="message" rows="2" maxlength="500" placeholder="On se voit à la LAN ?"><?= h($_POST['message'] ?? '') ?></textarea>
        </label>

        <div class="submit-bar">
            <button type="submit" class="btn primary block" id="tradeSubmit" disabled>Envoyer la proposition</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
