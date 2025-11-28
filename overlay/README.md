# 🎄 Lanoel Overlay - Guide d'installation

Overlay de classement en temps réel pour le tournoi Lanoel 2025.

## 📋 Prérequis

- Node.js (version 16 ou supérieure)
- npm ou yarn

## 🚀 Installation pour développement

1. **Installer les dépendances**
```bash
cd overlay
npm install
```

2. **Lancer l'overlay en mode développement**
```bash
npm start
```

## 📦 Compiler l'application pour distribution

### Windows
```bash
npm run build-win
```
Le fichier d'installation se trouvera dans `overlay/dist/Lanoel Overlay Setup.exe`

### macOS
```bash
npm run build-mac
```
Le fichier DMG se trouvera dans `overlay/dist/Lanoel Overlay.dmg`

### Linux
```bash
npm run build-linux
```
Le fichier AppImage se trouvera dans `overlay/dist/Lanoel Overlay.AppImage`

## ⌨️ Raccourcis clavier

| Raccourci | Action |
|-----------|--------|
| `Ctrl+L` | Verrouiller/Déverrouiller l'overlay (mode click-through) |
| `Ctrl+H` | Masquer/Afficher l'overlay |

## 🎮 Utilisation

### Premier lancement

1. L'overlay se positionne automatiquement en haut à droite de votre écran
2. Il affiche le classement en temps réel
3. L'actualisation se fait toutes les 30 secondes par défaut

### Mode verrouillage

- Cliquez sur le bouton 🔓 ou utilisez `Ctrl+L`
- En mode verrouillé (🔒), l'overlay laisse passer les clics (utile pendant les jeux)
- Les raccourcis clavier restent fonctionnels

### Paramètres

Cliquez sur ⚙️ pour accéder aux paramètres:

- **URL de l'API**: Personnaliser l'URL source (par défaut: https://lanoel.ch/api/leaderboard.php)
- **Intervalle d'actualisation**: Entre 5 et 300 secondes
- **Opacité**: Ajuster la transparence de l'overlay (50-100%)

### Déplacement et redimensionnement

- **Déplacer**: Cliquez et glissez sur la barre de titre
- **Redimensionner**: Tirez sur les bords de la fenêtre
- L'overlay reste toujours au premier plan

## 🎨 Fonctionnalités

✨ **Design moderne et élégant**
- Interface semi-transparente avec effet de flou
- Animations fluides
- Thème sombre optimisé pour le gaming

🏆 **Classement en temps réel**
- Top 3 mis en évidence avec médailles
- Affichage des points et joueurs
- Actualisation automatique configurable

🔧 **Personnalisable**
- Opacité réglable
- Position libre sur l'écran
- Intervalle d'actualisation personnalisable

⚡ **Performance**
- Faible consommation de ressources
- Compatible avec tous les jeux
- Mode click-through pour ne pas gêner

## 🛠️ Configuration technique

### Structure des fichiers

```
overlay/
├── main.js           # Process principal Electron
├── renderer.js       # Logique de l'interface
├── index.html        # Interface HTML
├── styles.css        # Styles CSS
├── package.json      # Configuration npm
└── README.md         # Ce fichier
```

### API utilisée

L'overlay consomme l'API REST:
- **Endpoint**: `/api/leaderboard.php`
- **Méthode**: GET
- **Format**: JSON

Exemple de réponse:
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "name": "Team Alpha",
      "points": 150,
      "player1": "Player1",
      "player2": "Player2"
    }
  ],
  "timestamp": 1234567890
}
```

## 🐛 Dépannage

### L'overlay ne se lance pas
- Vérifiez que Node.js est installé: `node --version`
- Réinstallez les dépendances: `npm install`

### Erreur de connexion
- Vérifiez que l'URL de l'API est correcte dans les paramètres
- Assurez-vous que le serveur est accessible
- Vérifiez votre connexion internet

### L'overlay disparaît
- Utilisez `Ctrl+H` pour le réafficher
- Relancez l'application si nécessaire

## 📝 Notes pour la distribution

### Fichiers à distribuer

Pour distribuer l'overlay aux utilisateurs:
1. Compilez l'application: `npm run build-win` (ou mac/linux)
2. Le fichier d'installation se trouve dans `dist/`
3. Distribuez ce fichier unique aux utilisateurs

### Installation utilisateur

Les utilisateurs n'ont besoin que de:
1. Télécharger le fichier d'installation
2. Double-cliquer pour installer
3. Lancer "Lanoel Overlay"

**Aucune installation de Node.js ou dépendances requise pour les utilisateurs finaux!**

## 📄 Licence

MIT - Libre d'utilisation et de modification

## 👥 Support

Pour toute question ou problème:
- Contactez l'équipe Lanoel
- Consultez la documentation sur lanoel.ch

---

**Bon tournoi! 🎮🎄**
