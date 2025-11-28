const { app, BrowserWindow, ipcMain, screen } = require('electron');
const path = require('path');

let mainWindow;
let isLocked = false;

function createWindow() {
    const { width, height } = screen.getPrimaryDisplay().workAreaSize;

    mainWindow = new BrowserWindow({
        width: 350,
        height: 600,
        x: width - 370,
        y: 20,
        transparent: true,
        frame: false,
        alwaysOnTop: true,
        skipTaskbar: false,
        resizable: true,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });

    mainWindow.setIgnoreMouseEvents(false);
    mainWindow.loadFile('index.html');

    // Raccourcis clavier
    mainWindow.webContents.on('before-input-event', (event, input) => {
        // Ctrl+L pour verrouiller/déverrouiller l'overlay
        if (input.control && input.key.toLowerCase() === 'l') {
            toggleLock();
        }
        // Ctrl+H pour masquer/afficher
        if (input.control && input.key.toLowerCase() === 'h') {
            if (mainWindow.isVisible()) {
                mainWindow.hide();
            } else {
                mainWindow.show();
            }
        }
    });
}

function toggleLock() {
    isLocked = !isLocked;
    mainWindow.setIgnoreMouseEvents(isLocked, { forward: true });
    mainWindow.webContents.send('lock-state-changed', isLocked);
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
        createWindow();
    }
});

ipcMain.on('toggle-lock', () => {
    toggleLock();
});
