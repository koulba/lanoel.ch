<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = "Télécharger l'Overlay";
include 'includes/header.php';
?>

<div class="container">
    <div class="download-hero">
        <h1 class="download-title">Overlay ⬇️</h1>
        <p class="download-subtitle">Suivez le classement en temps réel pendant que vous jouez</p>
    </div>

    <div class="download-preview">
        <div class="preview-card">
            <div class="preview-features">
                <h2>Fonctionnalités</h2>
                <ul class="features-list">
                    <li>Classement en temps réel</li>
                    <li>Actualisation automatique toutes les 30 secondes</li>
                    <li>Toujours visible par-dessus vos jeux</li>
                    <li>Mode verrouillage (clics passent à travers)</li>
                    <li>Personnalisable (opacité, position, intervalle)</li>
                    <li>Léger et rapide</li>
                </ul>
            </div>
        </div>

        <div class="download-section">
            <div class="download-card">
                <div class="download-icon">💻</div>
                <h3>Windows</h3>
                <p>Compatible Windows 10 et 11</p>
                <a href="downloads/Lanoel Overlay 1.0.0.exe" class="btn btn-primary btn-download" download>
                    📥 Télécharger pour Windows
                </a>
                <small class="file-info">Fichier portable • ~150 MB</small>
            </div>

            <!-- Placeholder pour futures versions -->
            <div class="download-card disabled">
                <div class="download-icon">🍎</div>
                <h3>macOS</h3>
                <p>Bientôt disponible</p>
                <button class="btn btn-secondary" disabled>Bientôt disponible</button>
            </div>

            <div class="download-card disabled">
                <div class="download-icon">🐧</div>
                <h3>Linux</h3>
                <p>Bientôt disponible</p>
                <button class="btn btn-secondary" disabled>Bientôt disponible</button>
            </div>
        </div>
    </div>

    <!-- Instructions d'installation -->
    <div class="instructions-section">
        <h2 class="section-title">📖 Installation</h2>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Télécharger</h3>
                <p>Cliquez sur le bouton de téléchargement ci-dessus</p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Lancer</h3>
                <p>Double-cliquez sur le fichier <code>Lanoel Overlay 1.0.0.exe</code></p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Profiter</h3>
                <p>L'overlay s'affiche automatiquement en haut à droite !</p>
            </div>
        </div>

        <!-- Note de sécurité Windows -->
        <div class="alert alert-info" style="margin-top: 30px;">
            <strong>⚠️ Note importante :</strong> Windows peut afficher un avertissement "Éditeur inconnu".
            C'est normal pour une application non signée. Cliquez sur <strong>"Plus d'informations"</strong>
            puis <strong>"Exécuter quand même"</strong>.
        </div>
    </div>

    <!-- Guide d'utilisation -->
    <div class="usage-section">
        <h2 class="section-title">🎮 Utilisation</h2>

        <div class="usage-grid">
            <div class="usage-card">
                <h3>⌨️ Raccourcis clavier</h3>
                <table class="shortcuts-table">
                    <tr>
                        <td><kbd>Ctrl</kbd> + <kbd>L</kbd></td>
                        <td>Verrouiller/Déverrouiller l'overlay</td>
                    </tr>
                    <tr>
                        <td><kbd>Ctrl</kbd> + <kbd>H</kbd></td>
                        <td>Masquer/Afficher l'overlay</td>
                    </tr>
                </table>
            </div>

            <div class="usage-card">
                <h3>🔧 Personnalisation</h3>
                <ul>
                    <li>Cliquez sur ⚙️ pour ouvrir les paramètres</li>
                    <li>Ajustez l'opacité avec le curseur</li>
                    <li>Modifiez l'intervalle d'actualisation (5-300s)</li>
                    <li>Déplacez l'overlay en glissant la barre de titre</li>
                </ul>
            </div>

            <div class="usage-card">
                <h3>🔒 Mode verrouillage</h3>
                <p>Activez le mode verrouillage (🔒) pour que les clics passent à travers l'overlay.
                Parfait quand vous jouez et ne voulez pas qu'il gêne !</p>
            </div>

            <div class="usage-card">
                <h3>💡 Conseils</h3>
                <ul>
                    <li>L'overlay reste toujours au premier plan</li>
                    <li>Positionnez-le là où il ne gêne pas votre jeu</li>
                    <li>Réduisez l'opacité si nécessaire</li>
                    <li>Le classement se met à jour automatiquement</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="faq-section">
        <h2 class="section-title">❓ Questions fréquentes</h2>

        <div class="faq-list">
            <div class="faq-item">
                <h3>L'overlay fonctionne-t-il avec tous les jeux ?</h3>
                <p>Oui ! L'overlay fonctionne avec tous les jeux en mode fenêtré ou plein écran sans bordure.
                Pour le plein écran exclusif, basculez en mode fenêtré.</p>
            </div>

            <div class="faq-item">
                <h3>L'overlay consomme-t-il beaucoup de ressources ?</h3>
                <p>Non, l'overlay est très léger et consomme très peu de CPU et RAM (~100 MB).
                Il n'affectera pas les performances de vos jeux.</p>
            </div>

            <div class="faq-item">
                <h3>Comment désinstaller l'overlay ?</h3>
                <p>Fermez simplement l'application et supprimez le fichier .exe.
                C'est une version portable qui ne nécessite pas d'installation.</p>
            </div>

            <div class="faq-item">
                <h3>L'overlay ne se connecte pas ?</h3>
                <p>Vérifiez votre connexion internet et que vous êtes bien connecté au site lanoel.ch.
                Si le problème persiste, actualisez manuellement avec le bouton 🔄.</p>
            </div>
        </div>
    </div>

    <div class="back-section">
        <a href="index.php" class="btn btn-secondary">← Retour à l'accueil</a>
    </div>
</div>

<style>
.download-hero {
    text-align: center;
    padding: 60px 20px 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    color: white;
    margin-bottom: 50px;
}

.download-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 15px;
    letter-spacing: -2px;
}

.download-subtitle {
    font-size: 1.2rem;
    opacity: 0.95;
}

.download-preview {
    margin-bottom: 60px;
}

.preview-card {
    background: var(--light-gray);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 40px;
}

.preview-features h2 {
    font-size: 1.8rem;
    margin-bottom: 25px;
    text-align: center;
}

.features-list {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    font-size: 1.1rem;
}

.features-list li {
    padding: 15px;
    background: white;
    border-radius: 12px;
    transition: var(--transition);
}

.features-list li:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.download-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.download-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.download-card:not(.disabled):hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.download-card.disabled {
    opacity: 0.5;
}

.download-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.download-card h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.download-card p {
    color: var(--gray);
    margin-bottom: 25px;
}

.btn-download {
    font-size: 1.1rem;
    padding: 15px 40px;
    margin-bottom: 15px;
}

.file-info {
    display: block;
    color: var(--gray);
    font-size: 0.85rem;
}

.instructions-section,
.usage-section,
.faq-section {
    margin-bottom: 60px;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.step-card {
    background: var(--light-gray);
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    transition: var(--transition);
}

.step-card:hover {
    transform: translateY(-5px);
    background: var(--dark-gray);
    color: white;
}

.step-number {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 auto 20px;
}

.step-card h3 {
    font-size: 1.3rem;
    margin-bottom: 10px;
}

code {
    background: rgba(102, 126, 234, 0.1);
    padding: 3px 8px;
    border-radius: 5px;
    font-family: monospace;
    color: #667eea;
}

.usage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.usage-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.usage-card h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.usage-card ul {
    list-style: none;
    padding-left: 0;
}

.usage-card ul li {
    padding: 8px 0;
    padding-left: 25px;
    position: relative;
}

.usage-card ul li:before {
    content: "▸";
    position: absolute;
    left: 0;
    color: #667eea;
    font-weight: bold;
}

.shortcuts-table {
    width: 100%;
}

.shortcuts-table td {
    padding: 12px 0;
    border-bottom: 1px solid var(--light-gray);
}

.shortcuts-table tr:last-child td {
    border-bottom: none;
}

kbd {
    background: var(--dark-gray);
    color: white;
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 0.9rem;
    font-family: monospace;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.faq-list {
    margin-top: 30px;
}

.faq-item {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.faq-item h3 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #667eea;
}

.back-section {
    text-align: center;
    margin-top: 60px;
    padding-top: 40px;
    border-top: 2px solid var(--light-gray);
}

@media (max-width: 768px) {
    .download-title {
        font-size: 2rem;
    }

    .features-list {
        grid-template-columns: 1fr;
    }

    .download-section,
    .steps-grid,
    .usage-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
