<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$votingClosed = isVotingClosed();

ensureGamesAddedBy($pdo);

// Proposer un nouveau jeu (nom + image obligatoires)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $name = trim($_POST['name'] ?? '');

    if ($votingClosed) {
        $error = "Les votes sont terminés ! Il n'est plus possible d'ajouter de jeu.";
    } elseif ($name === '') {
        $error = "Le nom du jeu est obligatoire.";
    } elseif (mb_strlen($name) > 100) {
        $error = "Le nom du jeu est trop long (100 caractères max).";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM games WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = "Ce jeu est déjà dans la liste !";
        } else {
            try {
                $image = uploadGameImage($_FILES['image'] ?? []);
                $stmt = $pdo->prepare("INSERT INTO games (name, image, added_by) VALUES (?, ?, ?)");
                $stmt->execute([$name, $image, $_SESSION['user_id']]);
                $success = "« " . $name . " » a été ajouté !";
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = "Ajouter un jeu";
include 'includes/header.php';
?>

<div class="container">
    <h2 class="section-title">Ajouter un jeu</h2>
    <p class="section-subtitle">Ton jeu n'est pas dans la liste ? Ajoute-le pour que tout le monde puisse voter pour lui.</p>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
            <a href="vote.php" class="btn btn-small btn-primary" style="margin-left: 10px;">Aller voter</a>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($votingClosed): ?>
        <div class="alert alert-error voting-closed">
            ⏰ La période de vote est terminée ! Il n'est plus possible d'ajouter de jeu.
        </div>
    <?php else: ?>
        <div class="auth-box auth-box-wide">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="game_name">Nom du jeu</label>
                    <input type="text" name="name" id="game_name" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="game_image">Image du jeu</label>
                    <div class="file-input-wrapper">
                        <label for="game_image" class="file-input-label">
                            📷 Choisir une image
                        </label>
                        <input type="file" name="image" id="game_image" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="showGameFileName(this)">
                    </div>
                    <div class="file-name" id="gameFileName"></div>
                    <small style="display: block; margin-top: 5px; color: var(--gray);">
                        Formats acceptés : JPG, PNG, GIF, WEBP (max 5Mo)
                    </small>
                </div>

                <button type="submit" name="add_game" class="btn btn-primary">Ajouter le jeu</button>
            </form>
        </div>

        <script>
        function showGameFileName(input) {
            const fileName = input.files[0]?.name || '';
            document.getElementById('gameFileName').textContent = fileName ? `📄 ${fileName}` : '';
        }
        </script>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
