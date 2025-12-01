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
        <h1 class="download-title">Overlay du classement</h1>
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
        <div class="alert alert-info security-note">
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

<?php include 'includes/footer.php'; ?>
