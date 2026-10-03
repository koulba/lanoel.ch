<?php
/**
 * Petite API JSON utilisée par la page Collection.
 * POST action=set_qty card_id=... qty=...
 */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

if (!isLoggedIn()) {
    respond(['error' => 'Non connecté'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Méthode non autorisée'], 405);
}
pokeCheckCsrf();

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'set_qty') {
    $cardId = (string)($_POST['card_id'] ?? '');
    $qty = max(0, min(99, (int)($_POST['qty'] ?? 0)));
    if (!pokeCard($cardId)) {
        respond(['error' => 'Carte inconnue'], 400);
    }
    $stmt = $pdo->prepare("INSERT INTO poke_cards (user_id, card_id, qty) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE qty = VALUES(qty)");
    $stmt->execute([$userId, $cardId, $qty]);
    respond(['ok' => true, 'card_id' => $cardId, 'qty' => $qty]);
}

respond(['error' => 'Action inconnue'], 400);
