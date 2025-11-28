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

<style>
.roulette-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.roulette-wheel {
    position: relative;
    width: 500px;
    height: 500px;
    margin: 40px auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 10px 50px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.roulette-inner {
    width: 90%;
    height: 90%;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    position: relative;
}

.participant-display {
    font-size: 2.5rem;
    font-weight: bold;
    color: #667eea;
    text-align: center;
    padding: 20px;
    transition: all 0.1s;
}

.participant-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 4px solid #667eea;
    transition: all 0.1s;
}

.roulette-indicator {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 20px solid transparent;
    border-right: 20px solid transparent;
    border-top: 30px solid #e74c3c;
    z-index: 100;
}

.controls {
    text-align: center;
    margin: 30px 0;
}

.btn-roulette {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 40px;
    font-size: 1.2rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s;
    margin: 0 10px;
}

.btn-roulette:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.btn-roulette:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.btn-reset {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

.selected-team {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    color: white;
    padding: 30px;
    border-radius: 20px;
    margin: 20px 0;
    text-align: center;
}

.selected-team h3 {
    margin: 0 0 20px 0;
    font-size: 2rem;
}

.team-members {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.team-member {
    text-align: center;
}

.team-member img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid white;
    margin-bottom: 10px;
}

.team-member-name {
    font-size: 1.3rem;
    font-weight: bold;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 3rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1.1rem;
    color: #666;
}

.drawn-list {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 15px;
    margin: 20px 0;
}

.drawn-list h3 {
    color: #667eea;
    margin-bottom: 15px;
}

.drawn-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.drawn-participant {
    background: white;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.drawn-participant img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-bottom: 8px;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin: 20px 0;
    text-align: center;
    font-weight: bold;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinning {
    animation: spin 0.1s linear infinite;
}
</style>

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
                display.style.background = 'white';
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
