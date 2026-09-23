<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer tous les palmarès archivés, groupés par année
$palmaresByYear = [];
try {
    $stmt = $pdo->query("
        SELECT p.*,
               u1.username as player1_name, u1.avatar as player1_avatar,
               u2.username as player2_name, u2.avatar as player2_avatar
        FROM palmares p
        LEFT JOIN users u1 ON p.player1_id = u1.id
        LEFT JOIN users u2 ON p.player2_id = u2.id
        ORDER BY p.year DESC, p.position ASC
    ");
    foreach ($stmt->fetchAll() as $row) {
        $palmaresByYear[$row['year']][] = $row;
    }
} catch (PDOException $e) {
    // La table palmarès n'existe pas encore : rien à afficher
}

$pageTitle = "Palmarès";
include 'includes/header.php';
?>

<div class="container">
    <h2 class="section-title">🏅 Palmarès</h2>
    <p class="section-subtitle">Le souvenir des éditions passées de la LANoël</p>

    <?php if (empty($palmaresByYear)): ?>
        <div class="alert alert-info">Aucun palmarès archivé pour le moment.</div>
    <?php else: ?>
        <?php foreach ($palmaresByYear as $year => $ranking): ?>
            <div class="palmares-year">
                <h3 class="palmares-year-title">🎄 LANoël <?= (int)$year ?></h3>

                <!-- Podium (top 3) -->
                <div class="palmares-podium">
                    <?php
                    $medals = ['🥇', '🥈', '🥉'];
                    $labels = ['Vainqueur', '2ème place', '3ème place'];
                    $podiumClasses = ['first', 'second', 'third'];
                    foreach (array_slice($ranking, 0, 3) as $i => $team):
                    ?>
                        <div class="palmares-podium-card <?= $podiumClasses[$i] ?>">
                            <div class="palmares-podium-medal"><?= $medals[$i] ?></div>
                            <div class="palmares-podium-label"><?= $labels[$i] ?></div>
                            <div class="palmares-podium-team"><?= htmlspecialchars($team['team_name']) ?></div>
                            <div class="palmares-podium-players">
                                <?php
                                $players = array_filter([$team['player1_name'], $team['player2_name']]);
                                echo $players ? htmlspecialchars(implode(' & ', $players)) : '—';
                                ?>
                            </div>
                            <div class="palmares-podium-points"><?= (int)$team['points'] ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Classement complet -->
                <div class="palmares-full-ranking">
                    <?php foreach ($ranking as $team):
                        $pos = (int)$team['position'];
                        $rowClass = $pos <= 3 ? $podiumClasses[$pos - 1] : '';
                    ?>
                        <div class="rank-item <?= $rowClass ?>">
                            <div class="rank-position">
                                <?= isset($medals[$pos - 1]) ? $medals[$pos - 1] : '#' . $pos ?>
                            </div>
                            <div class="rank-name">
                                <?= htmlspecialchars($team['team_name']) ?>
                                <?php
                                $players = array_filter([$team['player1_name'], $team['player2_name']]);
                                if ($players):
                                ?>
                                    <span class="rank-players"><?= htmlspecialchars(implode(' & ', $players)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rank-points"><?= (int)$team['points'] ?><span>pts</span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>
