<?php
require_once 'config/database.php';

// Récupérer le classement des équipes
$stmt = $pdo->query("SELECT name, points FROM teams ORDER BY points DESC");
$teams = $stmt->fetchAll();

$position = 1;
$medals = ['🥇', '🥈', '🥉'];
$classes = ['first', 'second', 'third'];

foreach ($teams as $team):
    $medal = isset($medals[$position - 1]) ? $medals[$position - 1] : "#$position";
    $class = isset($classes[$position - 1]) ? $classes[$position - 1] : '';
?>
    <div class="rank-item <?= $class ?>">
        <div class="rank-position"><?= $medal ?></div>
        <div class="rank-name"><?= htmlspecialchars($team['name']) ?></div>
        <div class="rank-points">
            <?= $team['points'] ?><span>pts</span>
        </div>
    </div>
<?php
    $position++;
endforeach;
?>
