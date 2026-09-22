<?php
// Script temporaire : sauvegarde puis vidage de la table votes (remise à zéro pour 2026)
// À supprimer après usage. Protégé par jeton secret.
header('Content-Type: text/plain; charset=utf-8');

$expectedToken = 'Lx9vQ2mR7tKpW4nB8sJcE3hZ';
if (!isset($_GET['token']) || !hash_equals($expectedToken, $_GET['token'])) {
    http_response_code(403);
    die("Accès refusé.\n");
}

require_once __DIR__ . '/config/database.php';

$count = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
echo "-- Votes actuels : $count\n";
echo "-- Sauvegarde table votes (LANoël 2025) avant remise à zéro pour 2026\n";

$rows = $pdo->query("SELECT * FROM votes")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $cols = implode('`, `', array_keys($row));
    $vals = implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row)));
    echo "INSERT INTO `votes` (`$cols`) VALUES ($vals);\n";
}
echo "-- Fin de la sauvegarde (" . count($rows) . " lignes)\n";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'oui') {
    echo "-- Mode lecture seule. Ajouter &confirm=oui pour vider la table.\n";
    exit;
}

$pdo->exec("TRUNCATE TABLE votes");
$after = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
echo "-- Table votes vidée. Votes restants : $after\n";
