<?php
/**
 * Socle commun de poke.lanoel.ch
 * Réutilise la base et les comptes de lanoel.ch (config/database.php).
 */
require_once __DIR__ . '/../../config/database.php';

// URL du site principal (avatars, inscription)
define('POKE_MAIN_URL', rtrim(getenv('MAIN_SITE_URL') ?: 'https://lanoel.ch', '/'));

// Création des tables au premier passage (une fois par session)
if (empty($_SESSION['poke_schema_v1'])) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS poke_cards (
        user_id INT NOT NULL,
        card_id VARCHAR(20) NOT NULL,
        qty INT UNSIGNED NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, card_id),
        KEY idx_card (card_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS poke_trades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user INT NOT NULL,
        to_user INT NOT NULL,
        status ENUM('pending','accepted','refused','cancelled','done') NOT NULL DEFAULT 'pending',
        message VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_from (from_user),
        KEY idx_to (to_user)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // side = 'give' : from_user donne la carte ; 'get' : from_user la reçoit
    $pdo->exec("CREATE TABLE IF NOT EXISTS poke_trade_items (
        trade_id INT NOT NULL,
        card_id VARCHAR(20) NOT NULL,
        side ENUM('give','get') NOT NULL,
        qty INT UNSIGNED NOT NULL DEFAULT 1,
        PRIMARY KEY (trade_id, card_id, side)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $_SESSION['poke_schema_v1'] = 1;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function pokeRequireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function pokeCsrfToken() {
    if (empty($_SESSION['poke_csrf'])) {
        $_SESSION['poke_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['poke_csrf'];
}

function pokeCheckCsrf() {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(pokeCsrfToken(), (string)$token)) {
        http_response_code(403);
        die('Session expirée, recharge la page.');
    }
}

/** Catalogue complet : ['sets' => [...], 'cards' => [id => carte]] */
function pokeCatalog() {
    static $catalog = null;
    if ($catalog === null) {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/cards.json'), true);
        $cards = [];
        foreach ($data['cards'] as $card) {
            $cards[$card['id']] = $card;
        }
        $catalog = ['sets' => $data['sets'], 'cards' => $cards];
    }
    return $catalog;
}

function pokeCard($id) {
    $cards = pokeCatalog()['cards'];
    return $cards[$id] ?? null;
}

/** Quantités d'un joueur : [card_id => qty] */
function pokeUserQty(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT card_id, qty FROM poke_cards WHERE user_id = ? AND qty > 0");
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
}

/** Les autres joueurs qui ont commencé leur collection, avec leurs quantités */
function pokeFriends(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.avatar, c.card_id, c.qty
        FROM poke_cards c JOIN users u ON u.id = c.user_id
        WHERE c.user_id <> ? AND c.qty > 0
        ORDER BY u.username");
    $stmt->execute([$userId]);
    $friends = [];
    foreach ($stmt as $row) {
        $id = (int)$row['id'];
        if (!isset($friends[$id])) {
            $friends[$id] = ['id' => $id, 'username' => $row['username'], 'avatar' => $row['avatar'], 'qty' => []];
        }
        $friends[$id]['qty'][$row['card_id']] = (int)$row['qty'];
    }
    return $friends;
}

function pokeAvatar($username, $avatar) {
    if (!empty($avatar)) {
        return POKE_MAIN_URL . '/uploads/avatars/' . rawurlencode($avatar);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&size=80&background=ffcb05&color=1a1200&bold=true';
}

function pokePendingCount(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM poke_trades WHERE to_user = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function pokeFlash($message = null) {
    if ($message !== null) {
        $_SESSION['poke_flash'] = $message;
        return null;
    }
    $message = $_SESSION['poke_flash'] ?? null;
    unset($_SESSION['poke_flash']);
    return $message;
}
