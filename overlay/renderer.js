const { ipcRenderer } = require('electron');

// Configuration par défaut
let config = {
    apiUrl: 'https://lanoel.ch/api/leaderboard.php',
    refreshInterval: 30000, // 30 secondes
    opacity: 0.95
};

// Charger la configuration depuis localStorage
function loadConfig() {
    const saved = localStorage.getItem('overlayConfig');
    if (saved) {
        config = { ...config, ...JSON.parse(saved) };
    }
    applyConfig();
}

// Sauvegarder la configuration
function saveConfig() {
    localStorage.setItem('overlayConfig', JSON.stringify(config));
    applyConfig();
}

// Appliquer la configuration
function applyConfig() {
    document.querySelector('.overlay-container').style.opacity = config.opacity;
    document.getElementById('apiUrl').value = config.apiUrl;
    document.getElementById('refreshInterval').value = config.refreshInterval / 1000;
    document.getElementById('opacity').value = config.opacity * 100;
    document.getElementById('opacityValue').textContent = Math.round(config.opacity * 100) + '%';
}

// État de l'application
let refreshTimer = null;
let isLocked = false;

// Éléments DOM
const leaderboardEl = document.getElementById('leaderboard');
const statusTextEl = document.getElementById('statusText');
const lastUpdateEl = document.getElementById('lastUpdate');
const lockBtn = document.getElementById('lockBtn');
const refreshBtn = document.getElementById('refreshBtn');
const settingsBtn = document.getElementById('settingsBtn');
const settingsPanel = document.getElementById('settingsPanel');
const saveSettingsBtn = document.getElementById('saveSettings');
const statusBar = document.querySelector('.status-bar');

// Récupérer le classement
async function fetchLeaderboard() {
    try {
        statusTextEl.textContent = 'Actualisation...';
        statusBar.className = 'status-bar';

        const response = await fetch(config.apiUrl);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.data) {
            displayLeaderboard(data.data);
            statusTextEl.textContent = '✓ Connecté';
            statusBar.className = 'status-bar connected';
            updateLastUpdate();
        } else {
            throw new Error('Données invalides');
        }
    } catch (error) {
        console.error('Erreur:', error);
        statusTextEl.textContent = '✗ Erreur de connexion';
        statusBar.className = 'status-bar error';
        leaderboardEl.innerHTML = `
            <div class="loading">
                ⚠️ Impossible de charger le classement<br>
                <small>${error.message}</small>
            </div>
        `;
    }
}

// Afficher le classement
function displayLeaderboard(teams) {
    if (!teams || teams.length === 0) {
        leaderboardEl.innerHTML = '<div class="loading">Aucune équipe pour le moment</div>';
        return;
    }

    const medals = ['🥇', '🥈', '🥉'];

    leaderboardEl.innerHTML = teams.map(team => {
        const medal = team.rank <= 3 ? medals[team.rank - 1] : '';
        const rankClass = `rank-${team.rank}`;

        return `
            <div class="team-item ${team.rank <= 3 ? rankClass : ''}">
                <div class="team-header">
                    <div class="team-rank">
                        <span class="rank-number">#${team.rank}</span>
                        ${medal ? `<span class="rank-medal">${medal}</span>` : ''}
                    </div>
                    <div class="team-name">${escapeHtml(team.name)}</div>
                    <div class="team-points">${team.points} pts</div>
                </div>
                ${team.player1 && team.player2 ? `
                    <div class="team-players">
                        👥 ${escapeHtml(team.player1)} & ${escapeHtml(team.player2)}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

// Échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Mettre à jour l'heure de dernière actualisation
function updateLastUpdate() {
    const now = new Date();
    const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    lastUpdateEl.textContent = `Mis à jour: ${time}`;
}

// Démarrer l'actualisation automatique
function startAutoRefresh() {
    stopAutoRefresh();
    refreshTimer = setInterval(fetchLeaderboard, config.refreshInterval);
}

// Arrêter l'actualisation automatique
function stopAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
}

// Basculer le verrouillage
function toggleLock() {
    isLocked = !isLocked;
    const container = document.querySelector('.overlay-container');

    if (isLocked) {
        container.classList.add('locked');
        lockBtn.textContent = '🔒';
        lockBtn.classList.add('locked');
    } else {
        container.classList.remove('locked');
        lockBtn.textContent = '🔓';
        lockBtn.classList.remove('locked');
    }

    ipcRenderer.send('toggle-lock');
}

// Basculer le panneau des paramètres
function toggleSettings() {
    settingsPanel.classList.toggle('hidden');
}

// Gérer les changements d'opacité
document.getElementById('opacity').addEventListener('input', (e) => {
    const value = e.target.value / 100;
    document.getElementById('opacityValue').textContent = e.target.value + '%';
    document.querySelector('.overlay-container').style.opacity = value;
});

// Event Listeners
lockBtn.addEventListener('click', toggleLock);
refreshBtn.addEventListener('click', () => {
    refreshBtn.classList.add('refreshing');
    fetchLeaderboard().finally(() => {
        setTimeout(() => refreshBtn.classList.remove('refreshing'), 500);
    });
});
settingsBtn.addEventListener('click', toggleSettings);

saveSettingsBtn.addEventListener('click', () => {
    config.apiUrl = document.getElementById('apiUrl').value;
    config.refreshInterval = parseInt(document.getElementById('refreshInterval').value) * 1000;
    config.opacity = parseInt(document.getElementById('opacity').value) / 100;

    saveConfig();
    toggleSettings();

    // Redémarrer l'actualisation avec le nouvel intervalle
    stopAutoRefresh();
    fetchLeaderboard();
    startAutoRefresh();
});

// Écouter les changements d'état de verrouillage
ipcRenderer.on('lock-state-changed', (event, locked) => {
    isLocked = locked;
    const container = document.querySelector('.overlay-container');

    if (isLocked) {
        container.classList.add('locked');
        lockBtn.textContent = '🔒';
        lockBtn.classList.add('locked');
    } else {
        container.classList.remove('locked');
        lockBtn.textContent = '🔓';
        lockBtn.classList.remove('locked');
    }
});

// Initialisation
loadConfig();
fetchLeaderboard();
startAutoRefresh();

// Rafraîchir au focus de la fenêtre
window.addEventListener('focus', () => {
    fetchLeaderboard();
});
