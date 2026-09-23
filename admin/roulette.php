<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Initialiser la session pour la roulette si elle n'existe pas
if (!isset($_SESSION['roulette_drawn'])) {
    $_SESSION['roulette_drawn'] = [];
}

// Réinitialiser la roulette
if (isset($_POST['reset'])) {
    $_SESSION['roulette_drawn'] = [];
    $_SESSION['success_message'] = "La roulette a été réinitialisée !";
    header('Location: roulette.php');
    exit();
}

// Récupérer tous les participants (non-admin)
$stmt = $pdo->query("
    SELECT id, username, avatar
    FROM users
    WHERE is_admin = 0
    ORDER BY username ASC
");
$allParticipants = $stmt->fetchAll();

// Filtrer les participants non encore tirés
$availableParticipants = array_filter($allParticipants, function($p) {
    return !in_array($p['id'], $_SESSION['roulette_drawn']);
});

// Participants déjà tirés (pour affichage)
$drawnParticipants = array_filter($allParticipants, function($p) {
    return in_array($p['id'], $_SESSION['roulette_drawn']);
});

$pageTitle = "Roulette - Tirage d'équipes";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="roulette-container">
    <h2 class="section-title">🎰 Roulette - Tirage d'équipes</h2>
    <p class="section-subtitle">Tirez au sort 2 participants pour former une équipe</p>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= count($allParticipants) ?></div>
            <div class="stat-label">👥 Total participants</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($availableParticipants) ?></div>
            <div class="stat-label">✅ Disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($drawnParticipants) ?></div>
            <div class="stat-label">🎯 Déjà tirés</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($_SESSION['roulette_drawn']) / 2 ?></div>
            <div class="stat-label">🏆 Équipes formées</div>
        </div>
    </div>

    <?php if (count($availableParticipants) < 2): ?>
        <div class="alert alert-warning">
            ⚠️ Pas assez de participants disponibles pour former une équipe ! (minimum 2 requis)
        </div>
    <?php endif; ?>

    <!-- Roulette -->
    <div class="roulette-wheel">
        <div class="roulette-indicator"></div>
        <div class="roulette-inner" id="rouletteDisplay">
            <div id="participantAvatar"></div>
            <div class="participant-display" id="participantName">
                Prêt à tirer !
            </div>
        </div>
    </div>

    <!-- Contrôles -->
    <div class="controls">
        <button class="btn-roulette" id="spinBtn" onclick="spinRoulette()" <?= count($availableParticipants) < 2 ? 'disabled' : '' ?>>
            🎲 Tirer 2 participants
        </button>

        <?php if (count($drawnParticipants) > 0): ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="reset" class="btn-roulette btn-reset" onclick="return confirm('Voulez-vous vraiment réinitialiser la roulette ?')">
                    🔄 Réinitialiser
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Équipe sélectionnée -->
    <div id="selectedTeam" style="display: none;">
        <div class="selected-team">
            <h3>🎉 Équipe tirée au sort !</h3>
            <div class="team-members" id="teamMembers"></div>
        </div>
    </div>

    <!-- Liste des participants déjà tirés -->
    <?php if (count($drawnParticipants) > 0): ?>
        <div class="drawn-list">
            <h3>🎯 Participants déjà tirés (<?= count($drawnParticipants) ?>)</h3>
            <div class="drawn-grid">
                <?php foreach ($drawnParticipants as $participant): ?>
                    <div class="drawn-participant">
                        <?php
                        $avatar_url = !empty($participant['avatar'])
                            ? '../uploads/avatars/' . htmlspecialchars($participant['avatar'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($participant['username']) . '&size=100&background=667eea&color=fff&bold=true';
                        ?>
                        <img src="<?= $avatar_url ?>" alt="<?= htmlspecialchars($participant['username']) ?>">
                        <div><?= htmlspecialchars($participant['username']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Participants disponibles (format JSON)
const availableParticipants = <?= json_encode(array_values($availableParticipants)) ?>;
let isSpinning = false;
let selectedParticipants = [];

function getAvatarUrl(participant) {
    if (participant.avatar) {
        return '../uploads/avatars/' + participant.avatar;
    }
    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(participant.username) + '&size=200&background=667eea&color=fff&bold=true';
}

function displayParticipant(participant) {
    const avatarDiv = document.getElementById('participantAvatar');
    const nameDiv = document.getElementById('participantName');

    avatarDiv.innerHTML = '<img src="' + getAvatarUrl(participant) + '" class="participant-avatar" alt="' + participant.username + '">';
    nameDiv.textContent = participant.username;
}

function spinRoulette() {
    if (isSpinning || availableParticipants.length < 2) return;

    isSpinning = true;
    selectedParticipants = [];
    document.getElementById('spinBtn').disabled = true;
    document.getElementById('selectedTeam').style.display = 'none';

    // Mélanger et sélectionner 2 participants
    const shuffled = [...availableParticipants].sort(() => Math.random() - 0.5);
    selectedParticipants = [shuffled[0], shuffled[1]];

    let currentIndex = 0;
    let speed = 50;
    let iterations = 0;
    const maxIterations = 50;

    const spinInterval = setInterval(() => {
        // Afficher un participant aléatoire
        const randomParticipant = availableParticipants[Math.floor(Math.random() * availableParticipants.length)];
        displayParticipant(randomParticipant);

        iterations++;

        // Ralentir progressivement
        if (iterations > maxIterations * 0.7) {
            speed += 10;
        }

        // Arrêter et afficher les gagnants
        if (iterations >= maxIterations) {
            clearInterval(spinInterval);
            showWinners();
        }
    }, speed);
}

function showWinners() {
    let currentWinner = 0;

    const showNextWinner = () => {
        if (currentWinner < 2) {
            displayParticipant(selectedParticipants[currentWinner]);

            // Flash effect
            const display = document.getElementById('rouletteDisplay');
            display.style.background = '#ffd700';
            setTimeout(() => {
                display.style.background = '';
            }, 300);

            currentWinner++;
            setTimeout(showNextWinner, 1500);
        } else {
            saveAndDisplayTeam();
        }
    };

    showNextWinner();
}

function saveAndDisplayTeam() {
    // Envoyer les IDs au serveur pour enregistrement en session
    const formData = new FormData();
    formData.append('participant1_id', selectedParticipants[0].id);
    formData.append('participant2_id', selectedParticipants[1].id);

    fetch('roulette_save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayFinalTeam();
        }
    });
}

function displayFinalTeam() {
    const teamDiv = document.getElementById('teamMembers');
    teamDiv.innerHTML = selectedParticipants.map(p => `
        <div class="team-member">
            <img src="${getAvatarUrl(p)}" alt="${p.username}">
            <div class="team-member-name">${p.username}</div>
        </div>
    `).join('');

    document.getElementById('selectedTeam').style.display = 'block';

    // Recharger la page après 3 secondes pour mettre à jour les stats
    setTimeout(() => {
        location.reload();
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>
