<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Ajouter des points
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_points'])) {
    $teamId = $_POST['team_id'];
    $gameId = $_POST['game_id'] ?? null;
    $points = intval($_POST['points']);
    $reason = $_POST['reason'] ?? '';
    
    if ($teamId && $points != 0) {
        // Ajouter les points à l'équipe
        $stmt = $pdo->prepare("UPDATE teams SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $teamId]);
        
        // Enregistrer dans l'historique
        $stmt = $pdo->prepare("INSERT INTO points_history (team_id, game_id, points, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teamId, $gameId, $points, $reason]);
        
        $success = "Points ajoutés avec succès !";
    }
}

// Supprimer une entrée de l'historique et ajuster les points
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Récupérer l'entrée
    $stmt = $pdo->prepare("SELECT team_id, points FROM points_history WHERE id = ?");
    $stmt->execute([$id]);
    $entry = $stmt->fetch();
    
    if ($entry) {
        // Retirer les points
        $stmt = $pdo->prepare("UPDATE teams SET points = points - ? WHERE id = ?");
        $stmt->execute([$entry['points'], $entry['team_id']]);
        
        // Supprimer l'entrée
        $stmt = $pdo->prepare("DELETE FROM points_history WHERE id = ?");
        $stmt->execute([$id]);
        
        $success = "Entrée supprimée et points ajustés !";
    }
}

// Récupérer toutes les équipes
$stmt = $pdo->query("SELECT id, name, points FROM teams ORDER BY name");
$teams = $stmt->fetchAll();

// Récupérer tous les jeux
$stmt = $pdo->query("SELECT id, name FROM games ORDER BY name");
$games = $stmt->fetchAll();

// Récupérer l'historique des points
$stmt = $pdo->query("
    SELECT ph.*, t.name as team_name, g.name as game_name
    FROM points_history ph
    LEFT JOIN teams t ON ph.team_id = t.id
    LEFT JOIN games g ON ph.game_id = g.id
    ORDER BY ph.created_at DESC
    LIMIT 50
");
$history = $stmt->fetchAll();

$pageTitle = "Gestion des points";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <div class="admin-header">
        <h1>⚡ Gestion des Points</h1>
        <a href="index.php" class="btn-secondary">← Retour</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire d'ajout de points -->
    <div class="card">
        <h2>Ajouter / Retirer des points</h2>
        <form method="POST" class="form">
            <div class="form-group">
                <label>Équipe *</label>
                <select name="team_id" required>
                    <option value="">Sélectionner une équipe</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>">
                            <?= htmlspecialchars($team['name']) ?> (<?= $team['points'] ?> pts)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jeu (optionnel)</label>
                <select name="game_id">
                    <option value="">Aucun jeu spécifique</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?= $game['id'] ?>">
                            <?= htmlspecialchars($game['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Points * (utilisez un nombre négatif pour retirer)</label>
                <input type="number" name="points" required placeholder="Ex: 10 ou -5">
                <small>Entrez un nombre positif pour ajouter, négatif pour retirer</small>
            </div>

            <div class="form-group">
                <label>Raison (optionnel)</label>
                <input type="text" name="reason" placeholder="Ex: Victoire sur Fortnite">
            </div>

            <button type="submit" name="add_points" class="btn-primary">💾 Enregistrer les points</button>
        </form>
    </div>

    <!-- Classement actuel -->
    <div class="card">
        <h2>🏆 Classement actuel</h2>
        <?php if (count($teams) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Équipe</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Trier par points décroissants
                    usort($teams, function($a, $b) {
                        return $b['points'] - $a['points'];
                    });
                    
                    $position = 1;
                    foreach ($teams as $team): 
                    ?>
                        <tr>
                            <td>
                                <?php if ($position == 1): ?>
                                    🥇
                                <?php elseif ($position == 2): ?>
                                    🥈
                                <?php elseif ($position == 3): ?>
                                    🥉
                                <?php else: ?>
                                    <?= $position ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($team['name']) ?></td>
                            <td><strong><?= $team['points'] ?></strong> pts</td>
                        </tr>
                    <?php 
                        $position++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">Aucune équipe créée</p>
        <?php endif; ?>
    </div>

    <!-- Historique -->
    <div class="card">
        <h2>📜 Historique des points (50 dernières entrées)</h2>
        <?php if (count($history) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Équipe</th>
                        <th>Jeu</th>
                        <th>Points</th>
                        <th>Raison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></td>
                            <td><?= htmlspecialchars($entry['team_name']) ?></td>
                            <td><?= $entry['game_name'] ? htmlspecialchars($entry['game_name']) : '-' ?></td>
                            <td>
                                <span class="<?= $entry['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $entry['points'] > 0 ? '+' : '' ?><?= $entry['points'] ?>
                                </span>
                            </td>
                            <td><?= $entry['reason'] ? htmlspecialchars($entry['reason']) : '-' ?></td>
                            <td>
                                <a href="?delete=<?= $entry['id'] ?>" 
                                   class="btn-delete"
                                   onclick="return confirm('Supprimer cette entrée et ajuster les points ?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">Aucun historique</p>
        <?php endif; ?>
    </div>
</div>

<style>
.text-success {
    color: #4CAF50;
    font-weight: bold;
}

.text-danger {
    color: #f44336;
    font-weight: bold;
}

.btn-delete {
    padding: 5px 10px;
    background: #f44336;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.btn-delete:hover {
    background: #d32f2f;
    transform: scale(1.05);
}

small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}
</style>

<?php include '../includes/footer.php'; ?>
