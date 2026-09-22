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

<style>
.palmares-year {
    margin-bottom: 60px;
}

.palmares-year-title {
    font-size: 1.8rem;
    text-align: center;
    margin-bottom: 30px;
    color: var(--black);
}

.palmares-podium {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.palmares-podium-card {
    background-color: var(--white);
    border: 1px solid var(--light-gray);
    border-radius: 18px;
    padding: 30px 20px;
    text-align: center;
    transition: var(--transition);
}

.palmares-podium-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.palmares-podium-card.first {
    border-top: 5px solid #FFD700;
}

.palmares-podium-card.second {
    border-top: 5px solid #C0C0C0;
}

.palmares-podium-card.third {
    border-top: 5px solid #CD7F32;
}

.palmares-podium-medal {
    font-size: 3rem;
    margin-bottom: 10px;
}

.palmares-podium-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--gray);
    font-weight: 600;
    margin-bottom: 10px;
}

.palmares-podium-team {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--black);
    margin-bottom: 5px;
}

.palmares-podium-players {
    color: var(--gray);
    margin-bottom: 15px;
}

.palmares-podium-points {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--black);
}

.palmares-full-ranking {
    display: grid;
    gap: 12px;
    max-width: 700px;
    margin: 0 auto;
}

.palmares-full-ranking .rank-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    background: var(--light-gray);
    border-radius: 12px;
    border-left: 4px solid transparent;
}

.palmares-full-ranking .rank-item.first { border-left-color: #FFD700; }
.palmares-full-ranking .rank-item.second { border-left-color: #C0C0C0; }
.palmares-full-ranking .rank-item.third { border-left-color: #CD7F32; }

.palmares-full-ranking .rank-position {
    font-size: 1.5rem;
    font-weight: 700;
    min-width: 55px;
}

.palmares-full-ranking .rank-name {
    flex: 1;
    font-size: 1.1rem;
    font-weight: 600;
}

.palmares-full-ranking .rank-players {
    display: block;
    font-size: 0.85rem;
    font-weight: 400;
    color: var(--gray);
}

.palmares-full-ranking .rank-points {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--black);
}

.palmares-full-ranking .rank-points span {
    font-size: 0.85rem;
    color: var(--gray);
    margin-left: 4px;
}

@media (max-width: 768px) {
    .palmares-podium {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
