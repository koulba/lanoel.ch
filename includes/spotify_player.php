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
