<?php
// Ton iframe Spotify (tu peux le changer facilement)
$spotify_embed = '<iframe data-testid="embed-iframe" style="border-radius:12px" src="https://open.spotify.com/embed/playlist/3uDML0iRP7XSaKQhnAFzRs?utm_source=generator&theme=0" width="100%" height="352" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>';

// Lien direct vers la playlist (pour ajouter des morceaux)
$spotify_playlist_link = "https://open.spotify.com/playlist/3uDML0iRP7XSaKQhnAFzRs";
?>

<!-- Bulle flottante Spotify -->
<div class="spotify-bubble" id="spotifyBubble">
    <button class="spotify-bubble-button" onclick="toggleSpotifyPlayer()" title="Ouvrir la playlist">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
        </svg>
        <span class="spotify-pulse"></span>
    </button>
    
    <div class="spotify-player-container" id="spotifyPlayerContainer">
        <div class="spotify-player-header">
            <div class="spotify-header-content">
                <h4>🎵 Playlist Collective</h4>
                <span class="spotify-status">En ligne</span>
            </div>
            <button class="spotify-close" onclick="toggleSpotifyPlayer()" title="Fermer">×</button>
        </div>
        
        <!-- Lecteur Spotify (iframe généré par Spotify) -->
        <div class="spotify-player-body">
            <?= $spotify_embed ?>
        </div>
        
        <div class="spotify-footer">
            <a href="<?= $spotify_playlist_link ?>" target="_blank" class="spotify-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:5px;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                </svg>
                Ajouter des morceaux sur Spotify
            </a>
        </div>
    </div>
</div>

<style>
/* Bulle flottante */
.spotify-bubble {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9998;
}

.spotify-bubble-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1DB954 0%, #1ed760 100%);
    border: 3px solid var(--white);
    color: var(--white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 20px rgba(29, 185, 84, 0.4);
    position: relative;
    overflow: visible;
}

.spotify-bubble-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 30px rgba(29, 185, 84, 0.6);
}

.spotify-bubble-button:active {
    transform: scale(0.95);
}

.spotify-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(29, 185, 84, 0.4);
    animation: spotifyPulse 2s infinite;
    pointer-events: none;
}

@keyframes spotifyPulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* Container du lecteur */
.spotify-player-container {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 400px;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.9);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 9997;
}

.spotify-player-container.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.spotify-player-header {
    background: linear-gradient(135deg, #1DB954 0%, #1ed760 100%);
    color: var(--white);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spotify-header-content h4 {
    margin: 0 0 4px 0;
    font-size: 1rem;
    font-weight: 600;
}

.spotify-status {
    font-size: 0.75rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
}

.spotify-status::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--white);
    border-radius: 50%;
    margin-right: 6px;
    animation: blink 2s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.spotify-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: var(--white);
    font-size: 1.8rem;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
    line-height: 1;
}

.spotify-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.spotify-player-body {
    padding: 0;
    background: #121212;
}

.spotify-player-body iframe {
    display: block;
    border-radius: 0 !important;
}

.spotify-footer {
    padding: 15px 20px;
    background: linear-gradient(to top, #f8f8f8, var(--white));
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

.spotify-link {
    color: #1DB954;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.spotify-link:hover {
    color: #1ed760;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .spotify-bubble {
        bottom: 20px;
        right: 20px;
    }
    
    .spotify-bubble-button {
        width: 50px;
        height: 50px;
    }
    
    .spotify-player-container {
        width: calc(100vw - 40px);
        right: 20px;
        bottom: 80px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
}

/* Fluidité sur iOS */
.spotify-player-container {
    -webkit-overflow-scrolling: touch;
}
</style>

<script>
function toggleSpotifyPlayer() {
    const container = document.getElementById('spotifyPlayerContainer');
    container.classList.toggle('active');
}

// Fermer en cliquant en dehors
document.addEventListener('click', function(event) {
    const bubble = document.getElementById('spotifyBubble');
    const container = document.getElementById('spotifyPlayerContainer');
    
    if (!bubble.contains(event.target) && container.classList.contains('active')) {
        container.classList.remove('active');
    }
});

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const container = document.getElementById('spotifyPlayerContainer');
        if (container.classList.contains('active')) {
            container.classList.remove('active');
        }
    }
});

// Animation au chargement
window.addEventListener('load', function() {
    setTimeout(function() {
        const bubble = document.querySelector('.spotify-bubble-button');
        bubble.style.animation = 'spotifyBounce 0.6s ease';
    }, 500);
});

</script>
