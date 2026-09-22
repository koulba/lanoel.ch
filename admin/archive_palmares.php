<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Créer la table palmarès si elle n'existe pas encore
$pdo->exec("
    CREATE TABLE IF NOT EXISTS palmares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year INT NOT NULL,
        position INT NOT NULL,
        team_name VARCHAR(255) NOT NULL,
        player1_id INT DEFAULT NULL,
        player2_id INT DEFAULT NULL,
        points INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_year_position (year, position)
    )
");

// Archiver le classement actuel des équipes dans le palmarès
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive'])) {
    $year = intval($_POST['year']);
    $overwrite = isset($_POST['overwrite']);

    if ($year < 2000 || $year > 2100) {
        $error = "Année invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM palmares WHERE year = ?");
        $stmt->execute([$year]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists && !$overwrite) {
            $error = "Le palmarès $year existe déjà. Coche « Écraser » pour le remplacer.";
        } else {
            // Classement final = équipes triées par points décroissants
            $stmt = $pdo->query("
                SELECT name, player1_id, player2_id, points
                FROM teams
                ORDER BY points DESC, name ASC
            ");
            $teams = $stmt->fetchAll();

            if (empty($teams)) {
                $error = "Aucune équipe à archiver.";
            } else {
                if ($exists) {
                    $del = $pdo->prepare("DELETE FROM palmares WHERE year = ?");
                    $del->execute([$year]);
                }

                $insert = $pdo->prepare("
                    INSERT INTO palmares (year, position, team_name, player1_id, player2_id, points)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $position = 1;
                foreach ($teams as $team) {
                    $insert->execute([
                        $year,
                        $position,
                        $team['name'],
                        $team['player1_id'] ?: null,
                        $team['player2_id'] ?: null,
                        $team['points']
                    ]);
                    $position++;
                }

                $success = "Classement $year archivé avec succès (" . count($teams) . " équipes) ! 🏆";
            }
        }
    }
}

// Supprimer le palmarès d'une année
if (isset($_GET['delete_year'])) {
    $year = intval($_GET['delete_year']);
    $stmt = $pdo->prepare("DELETE FROM palmares WHERE year = ?");
    $stmt->execute([$year]);
    $success = "Palmarès $year supprimé.";
}

// Aperçu du classement actuel
$stmt = $pdo->query("
    SELECT t.name, t.points,
           u1.username as player1_name,
           u2.username as player2_name
    FROM teams t
    LEFT JOIN users u1 ON t.player1_id = u1.id
    LEFT JOIN users u2 ON t.player2_id = u2.id
    ORDER BY t.points DESC, t.name ASC
");
$currentTeams = $stmt->fetchAll();

// Années déjà archivées
$stmt = $pdo->query("
    SELECT year, COUNT(*) as team_count
    FROM palmares
    GROUP BY year
    ORDER BY year DESC
");
$archivedYears = $stmt->fetchAll();

$pageTitle = "Archiver le palmarès";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">🏅 Archiver le palmarès</h2>
    <p class="section-subtitle">
        Fige le classement final actuel des équipes comme souvenir permanent.
        À faire une fois à la fin de l'édition, <strong>avant</strong> de remettre les points à zéro.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire d'archivage -->
    <div class="auth-box" style="max-width: 600px;">
        <h3>Archiver le classement actuel</h3>
        <form method="POST">
            <div class="form-group">
                <label>Année de l'édition</label>
                <input type="number" name="year" value="2025" min="2000" max="2100" required>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                    <input type="checkbox" name="overwrite" style="width: auto;">
                    Écraser si cette année existe déjà
                </label>
            </div>
            <button type="submit" name="archive" class="btn btn-primary"
                    onclick="return confirm('Archiver le classement actuel dans le palmarès ?')">
                💾 Archiver ce classement
            </button>
        </form>
    </div>

    <!-- Années déjà archivées -->
    <?php if (!empty($archivedYears)): ?>
        <h3 style="margin-top: 50px; font-size: 1.5rem;">Palmarès archivés</h3>
        <table>
            <thead>
                <tr>
                    <th>Année</th>
                    <th>Équipes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archivedYears as $y): ?>
                    <tr>
                        <td><strong>LANoël <?= $y['year'] ?></strong></td>
                        <td><?= $y['team_count'] ?> équipes</td>
                        <td class="admin-actions">
                            <a href="../palmares.php" class="btn btn-small btn-secondary" target="_blank">Voir</a>
                            <a href="?delete_year=<?= $y['year'] ?>"
                               onclick="return confirm('Supprimer définitivement le palmarès <?= $y['year'] ?> ?')"
                               class="btn btn-small btn-danger">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Aperçu du classement actuel -->
    <h3 style="margin-top: 50px; font-size: 1.5rem;">Aperçu du classement actuel</h3>
    <?php if (empty($currentTeams)): ?>
        <div class="alert alert-info">Aucune équipe en base actuellement.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Équipe</th>
                    <th>Joueurs</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($currentTeams as $index => $team): ?>
                    <tr>
                        <td>
                            <?php
                            $medals = ['🥇', '🥈', '🥉'];
                            echo $medals[$index] ?? '#' . ($index + 1);
                            ?>
                        </td>
                        <td><strong><?= htmlspecialchars($team['name']) ?></strong></td>
                        <td>
                            <?= $team['player1_name'] ? htmlspecialchars($team['player1_name']) : '-' ?>
                            &amp;
                            <?= $team['player2_name'] ? htmlspecialchars($team['player2_name']) : '-' ?>
                        </td>
                        <td><strong><?= $team['points'] ?> pts</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="admin-actions-grid" style="margin-top: 40px;">
        <a href="index.php" class="btn btn-secondary">← Retour au dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
