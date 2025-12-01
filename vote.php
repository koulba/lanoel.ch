<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Vérifier si les votes sont terminés
$votingClosed = isVotingClosed();

// Compter les votes de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userVoteCount = $stmt->fetch()['vote_count'];

// Traiter le vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    if ($votingClosed) {
        $error = "Les votes sont terminés ! Il n'est plus possible de voter.";
    } else {
        $gameId = $_POST['game_id'];

        // Vérifier si l'utilisateur a déjà voté pour ce jeu
        $stmt = $pdo->prepare("SELECT id FROM votes WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$_SESSION['user_id'], $gameId]);
        $alreadyVoted = $stmt->fetch();

        if ($alreadyVoted) {
            // Retirer le vote
            $stmt = $pdo->prepare("DELETE FROM votes WHERE user_id = ? AND game_id = ?");
            $stmt->execute([$_SESSION['user_id'], $gameId]);
            $success = "Vote retiré !";
        } else {
            // Vérifier la limite de 8 votes
            if ($userVoteCount >= 8) {
                $error = "Vous avez atteint la limite de 8 votes !";
            } else {
                // Ajouter le vote
                $stmt = $pdo->prepare("INSERT INTO votes (user_id, game_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $gameId]);
                $success = "Vote enregistré !";
            }
        }

        // Recompter les votes
        $stmt = $pdo->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userVoteCount = $stmt->fetch()['vote_count'];
    }
}

// Récupérer tous les jeux avec le statut de vote de l'utilisateur
$stmt = $pdo->prepare("
    SELECT g.*,
           COUNT(DISTINCT v.id) as vote_count,
           MAX(CASE WHEN v.user_id = ? THEN 1 ELSE 0 END) as user_voted
    FROM games g
    LEFT JOIN votes v ON g.id = v.game_id
    GROUP BY g.id
    ORDER BY vote_count DESC
");
$stmt->execute([$_SESSION['user_id']]);
$games = $stmt->fetchAll();

$pageTitle = "Voter";
include 'includes/header.php';
?>

<div class="container">
    <h2 class="section-title">🗳️ Votez pour vos jeux préférés</h2>
    <p class="section-subtitle">Vous pouvez voter pour maximum 8 jeux</p>

    <?php if ($votingClosed): ?>
        <div class="alert alert-error voting-closed">
            ⏰ La période de vote est terminée ! Il n'est plus possible de voter.
        </div>
    <?php else: ?>
        <div class="vote-info">
            <strong><?= $userVoteCount ?> / 8</strong> votes utilisés
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (empty($games)): ?>
        <div class="alert alert-info">Aucun jeu n'a encore été ajouté.</div>
    <?php else: ?>
        <div class="games-grid">
            <?php foreach ($games as $game): ?>
                <div class="game-card">
                    <?php if ($game['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                    <?php else: ?>
                        <div class="game-placeholder">
                            🎮
                        </div>
                    <?php endif; ?>
                    
                    <div class="game-card-content">
                        <h3><?= htmlspecialchars($game['name']) ?></h3>
                        <div class="vote-count">
                            <span class="vote-emoji">👍</span>
                            <strong><?= $game['vote_count'] ?></strong> vote<?= $game['vote_count'] > 1 ? 's' : '' ?>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                            <button type="submit" class="btn vote-btn <?= $game['user_voted'] ? 'voted' : 'btn-primary' ?>" <?= $votingClosed ? 'disabled' : '' ?>>
                                <?= $votingClosed ? '🔒 Votes terminés' : ($game['user_voted'] ? '✓ Voté' : 'Voter') ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
