<?php
require_once __DIR__ . '/includes/bootstrap.php';
pokeRequireLogin();

$userId = (int)$_SESSION['user_id'];
$catalog = pokeCatalog();
$myQty = pokeUserQty($pdo, $userId);

// Pour chaque carte : qui l'a en double, et à qui elle manque
$offers = [];
$needs = [];
foreach (pokeFriends($pdo, $userId) as $friend) {
    foreach ($catalog['cards'] as $id => $card) {
        $qty = $friend['qty'][$id] ?? 0;
        if ($qty >= 2) {
            $offers[$id][] = ['id' => $friend['id'], 'name' => $friend['username'], 'spare' => $qty - 1];
        } elseif ($qty === 0) {
            $needs[$id][] = ['id' => $friend['id'], 'name' => $friend['username']];
        }
    }
}

$pageTitle = 'Ma collection';
$activeTab = 'collection';
include __DIR__ . '/includes/layout_top.php';
?>

<section class="collection" id="collection">
    <div class="sticky">
        <div class="seg" role="tablist" id="setTabs">
            <?php foreach ($catalog['sets'] as $i => $set): ?>
                <button type="button" role="tab" data-set="<?= h($set['id']) ?>" class="<?= $i === 0 ? 'on' : '' ?>">
                    <?= h($set['name']) ?>
                    <small data-progress="<?= h($set['id']) ?>"></small>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="bar"><i id="progressBar"></i></div>
        <div class="tools">
            <input type="search" id="search" placeholder="Rechercher une carte…" autocomplete="off">
        </div>
        <div class="chips" id="filters">
            <button type="button" data-filter="all" class="on">Toutes</button>
            <button type="button" data-filter="missing">Manquantes</button>
            <button type="button" data-filter="owned">Obtenues</button>
            <button type="button" data-filter="doubles">Doubles</button>
            <button type="button" data-filter="offered">Dispo chez amis</button>
        </div>
    </div>

    <div class="grid" id="grid"></div>
    <p class="empty" id="empty" hidden>Aucune carte ici.</p>
</section>

<dialog class="sheet" id="cardSheet">
    <form method="dialog" class="sheet-close"><button aria-label="Fermer">×</button></form>
    <div class="sheet-body" id="sheetBody"></div>
</dialog>

<script>
window.POKE = <?= json_encode([
    'sets' => $catalog['sets'],
    'cards' => array_values($catalog['cards']),
    'qty' => (object)$myQty,
    'offers' => (object)$offers,
    'needs' => (object)$needs,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
