<?php
// Attend : $pageTitle, $activeTab ('collection' | 'market' | 'trades'), $pdo
$pokeUser = null;
$pokePending = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $pokeUser = $stmt->fetch();
    $pokePending = pokePendingCount($pdo, $_SESSION['user_id']);
}
$flash = pokeFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b0b10">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf" content="<?= h(pokeCsrfToken()) ?>">
    <title><?= h($pageTitle ?? 'Poké Lanoël') ?> · Poké Lanoël</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
    <link rel="stylesheet" href="css/poke.css?v=1">
</head>
<body>
<header class="top">
    <a href="index.php" class="brand" aria-label="Accueil">
        <span class="ball" aria-hidden="true"></span>
        <span>Poké<b>Lanoël</b></span>
    </a>
    <?php if ($pokeUser): ?>
        <a href="logout.php" class="me" title="Se déconnecter">
            <img src="<?= h(pokeAvatar($pokeUser['username'], $pokeUser['avatar'])) ?>" alt="">
            <span><?= h($pokeUser['username']) ?></span>
        </a>
    <?php endif; ?>
</header>

<?php if ($flash): ?>
    <div class="flash" role="status"><?= h($flash) ?></div>
<?php endif; ?>

<main class="page">
