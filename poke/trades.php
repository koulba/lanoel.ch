<?php
/**
 * Mes échanges : propositions reçues, envoyées, en cours et historique.
 * Cycle : pending -> accepted -> done (collections mises à jour)
 *                 -> refused / cancelled
 */
require_once __DIR__ . '/includes/bootstrap.php';
pokeRequireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pokeCheckCsrf();
    $tradeId = (int)($_POST['trade_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM poke_trades WHERE id = ? AND (from_user = ? OR to_user = ?)");
    $stmt->execute([$tradeId, $userId, $userId]);
    $trade = $stmt->fetch();

    $isSender = $trade && (int)$trade['from_user'] === $userId;
    $setStatus = function ($status) use ($pdo, $tradeId) {
        $pdo->prepare("UPDATE poke_trades SET status = ? WHERE id = ?")->execute([$status, $tradeId]);
    };

    if (!$trade) {
        pokeFlash("Échange introuvable.");
    } elseif ($action === 'accept' && !$isSender && $trade['status'] === 'pending') {
        $setStatus('accepted');
        pokeFlash("Échange accepté ! Il ne reste plus qu'à vous voir pour échanger les cartes.");
    } elseif ($action === 'refuse' && !$isSender && $trade['status'] === 'pending') {
        $setStatus('refused');
        pokeFlash("Proposition refusée.");
    } elseif ($action === 'cancel' && in_array($trade['status'], ['pending', 'accepted'], true)
              && ($isSender || $trade['status'] === 'accepted')) {
        $setStatus('cancelled');
        pokeFlash("Échange annulé.");
    } elseif ($action === 'done' && $trade['status'] === 'accepted') {
        // Les cartes changent de main : on met à jour les deux collections
        $items = $pdo->prepare("SELECT card_id, side, qty FROM poke_trade_items WHERE trade_id = ?");
        $items->execute([$tradeId]);
        $items = $items->fetchAll();
        $from = (int)$trade['from_user'];
        $to = (int)$trade['to_user'];

        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT qty FROM poke_cards WHERE user_id = ? AND card_id = ? FOR UPDATE");
        $dec = $pdo->prepare("UPDATE poke_cards SET qty = qty - ? WHERE user_id = ? AND card_id = ?");
        $inc = $pdo->prepare("INSERT INTO poke_cards (user_id, card_id, qty) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
        $missing = [];
        foreach ($items as $item) {
            [$giver, $receiver] = $item['side'] === 'give' ? [$from, $to] : [$to, $from];
            $lock->execute([$giver, $item['card_id']]);
            if ((int)$lock->fetchColumn() < (int)$item['qty']) {
                $card = pokeCard($item['card_id']);
                $missing[] = $card ? $card['name'] : $item['card_id'];
                continue;
            }
            $dec->execute([$item['qty'], $giver, $item['card_id']]);
            $inc->execute([$receiver, $item['card_id'], $item['qty']]);
        }
        if ($missing) {
            $pdo->rollBack();
            pokeFlash("Impossible : carte(s) plus dans la collection de celui qui donne : " . implode(', ', $missing) . ".");
        } else {
            $setStatus('done');
            $pdo->commit();
            pokeFlash("Échange terminé, les collections sont à jour !");
        }
    } else {
        pokeFlash("Action impossible pour cet échange.");
    }
    redirect('trades.php');
}

// Chargement des échanges et de leurs cartes
$stmt = $pdo->prepare("SELECT t.*, uf.username AS from_name, uf.avatar AS from_avatar,
        ut.username AS to_name, ut.avatar AS to_avatar
    FROM poke_trades t
    JOIN users uf ON uf.id = t.from_user
    JOIN users ut ON ut.id = t.to_user
    WHERE t.from_user = ? OR t.to_user = ?
    ORDER BY t.updated_at DESC, t.id DESC
    LIMIT 100");
$stmt->execute([$userId, $userId]);
$trades = $stmt->fetchAll();

$itemsByTrade = [];
if ($trades) {
    $ids = array_column($trades, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM poke_trade_items WHERE trade_id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt as $item) {
        $itemsByTrade[$item['trade_id']][] = $item;
    }
}

$groups = [
    'todo' => ['title' => 'À traiter', 'trades' => []],
    'accepted' => ['title' => 'À faire en vrai', 'trades' => []],
    'sent' => ['title' => 'En attente de réponse', 'trades' => []],
    'history' => ['title' => 'Historique', 'trades' => []],
];
foreach ($trades as $t) {
    $isSender = (int)$t['from_user'] === $userId;
    if ($t['status'] === 'pending') $groups[$isSender ? 'sent' : 'todo']['trades'][] = $t;
    elseif ($t['status'] === 'accepted') $groups['accepted']['trades'][] = $t;
    else $groups['history']['trades'][] = $t;
}

$statusLabels = [
    'pending' => 'En attente',
    'accepted' => 'Accepté',
    'refused' => 'Refusé',
    'cancelled' => 'Annulé',
    'done' => 'Terminé',
];

$pageTitle = 'Mes échanges';
$activeTab = 'trades';
include __DIR__ . '/includes/layout_top.php';

function tradeCards(array $items) {
    if (!$items) return '<span class="muted small">Rien</span>';
    $html = '';
    foreach ($items as $item) {
        $card = pokeCard($item['card_id']);
        if (!$card) continue;
        $html .= '<figure><img loading="lazy" referrerpolicy="no-referrer" src="' . h($card['img']) . '" alt="">'
            . '<figcaption>' . h($card['name']) . ((int)$item['qty'] > 1 ? ' ×' . (int)$item['qty'] : '') . '</figcaption></figure>';
    }
    return $html;
}

function actionButton($tradeId, $action, $label, $class = '') {
    return '<form method="POST"><input type="hidden" name="csrf" value="' . h(pokeCsrfToken()) . '">'
        . '<input type="hidden" name="trade_id" value="' . (int)$tradeId . '">'
        . '<button class="btn ' . $class . '" name="action" value="' . h($action) . '">' . h($label) . '</button></form>';
}
?>

<section>
    <h1 class="h1">Mes échanges</h1>

    <?php if (!$trades): ?>
        <p class="empty">Aucun échange pour l'instant. Va voir la <a class="link" href="market.php">bourse</a> !</p>
    <?php endif; ?>

    <?php foreach ($groups as $key => $group): ?>
        <?php if (!$group['trades']) continue; ?>
        <h2 class="h2"><?= h($group['title']) ?> <span class="count"><?= count($group['trades']) ?></span></h2>
        <div class="stack">
            <?php foreach ($group['trades'] as $t):
                $isSender = (int)$t['from_user'] === $userId;
                $otherName = $isSender ? $t['to_name'] : $t['from_name'];
                $otherAvatar = $isSender ? $t['to_avatar'] : $t['from_avatar'];
                $items = $itemsByTrade[$t['id']] ?? [];
                $give = array_filter($items, function ($i) { return $i['side'] === 'give'; });
                $get = array_filter($items, function ($i) { return $i['side'] === 'get'; });
                // Du point de vue de la personne connectée
                $iGive = $isSender ? $give : $get;
                $iGet = $isSender ? $get : $give;
            ?>
                <article class="trade <?= h($t['status']) ?>">
                    <header>
                        <img class="avatar" src="<?= h(pokeAvatar($otherName, $otherAvatar)) ?>" alt="">
                        <div>
                            <strong><?= $isSender ? 'Vers ' : 'De ' ?><?= h($otherName) ?></strong>
                            <span class="muted small"><?= h(date('d.m.Y H:i', strtotime($t['updated_at']))) ?></span>
                        </div>
                        <span class="tag <?= h($t['status']) ?>"><?= h($statusLabels[$t['status']]) ?></span>
                    </header>

                    <?php if (!empty($t['message'])): ?>
                        <p class="msg">« <?= h($t['message']) ?> »</p>
                    <?php endif; ?>

                    <div class="swap">
                        <div>
                            <h3>Tu reçois</h3>
                            <div class="swap-cards"><?= tradeCards($iGet) ?></div>
                        </div>
                        <div>
                            <h3>Tu donnes</h3>
                            <div class="swap-cards"><?= tradeCards($iGive) ?></div>
                        </div>
                    </div>

                    <div class="actions">
                        <?php if ($t['status'] === 'pending' && !$isSender): ?>
                            <?= actionButton($t['id'], 'refuse', 'Refuser', 'ghost') ?>
                            <?= actionButton($t['id'], 'accept', 'Accepter', 'primary') ?>
                        <?php elseif ($t['status'] === 'pending'): ?>
                            <?= actionButton($t['id'], 'cancel', 'Annuler la proposition', 'ghost') ?>
                        <?php elseif ($t['status'] === 'accepted'): ?>
                            <?= actionButton($t['id'], 'cancel', 'Annuler', 'ghost') ?>
                            <?= actionButton($t['id'], 'done', 'Cartes échangées ✓', 'primary') ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
