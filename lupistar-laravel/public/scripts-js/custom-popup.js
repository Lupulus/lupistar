/**
 * Custom Popup Manager - Wolf Film
 * Gestionnaire de popups personnalisées pour remplacer les alert() et confirm() natifs
 */

class CustomPopupManager {
    constructor() {
        this.overlay = null;
        this.popup = null;
        this.currentResolve = null;
        this.isInitialized = false;
        this.queue = [];
        this.isShowing = false;
    }

    /**
     * Initialise le gestionnaire de popups
     */
    init() {
        if (this.isInitialized) return;
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupElements());
        } else {
            this.setupElements();
        }
    }

    /**
     * Configure les éléments DOM
     */
    setupElements() {
        this.overlay = document.getElementById('custom-popup-overlay');
        this.popup = this.overlay?.querySelector('.custom-popup');
        
        if (!this.overlay || !this.popup) {
            console.warn('Custom popup elements not found. Make sure popup.php is included.');
            return;
        }

        this.setupEventListeners();
        this.isInitialized = true;
        
        // Traiter la queue si elle existe
        this.processQueue();
    }

    /**
     * Configure les écouteurs d'événements
     */
    setupEventListeners() {
        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay.classList.contains('show')) {
                this.close(false);
            }
        });

        // Fermer en cliquant sur l'overlay
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close(false);
            }
        });
    }

    /**
     * Traite la queue des popups en attente
     */
    processQueue() {
        if (this.queue.length > 0 && !this.isShowing) {
            const nextPopup = this.queue.shift();
            this.show(nextPopup.options).then(nextPopup.resolve);
        }
    }

    /**
     * Affiche une popup avec les options spécifiées
     */
    show(options = {}) {
        return new Promise((resolve) => {
            if (!this.isInitialized) {
                // Ajouter à la queue si pas encore initialisé
                this.queue.push({ options, resolve });
                this.init();
                return;
            }

            if (this.isShowing) {
                // Ajouter à la queue si une popup est déjà affichée
                this.queue.push({ options, resolve });
                return;
            }

            this.isShowing = true;
            this.currentResolve = resolve;

            const {
                title = 'Confirmation',
                message = 'Êtes-vous sûr ?',
                type = 'confirm', // 'confirm', 'alert', 'success'
                confirmText = 'Confirmer',
                cancelText = 'Annuler',
                showCancel = true,
                confirmClass = 'primary',
                icon = '?'
            } = options;

            // Mettre à jour le contenu
            document.getElementById('custom-popup-title').textContent = title;
            document.getElementById('custom-popup-message').textContent = message;
            
            // Mettre à jour l'icône
            const iconElement = document.getElementById('custom-popup-icon');
            const iconText = document.getElementById('custom-popup-icon-text');
            
            iconElement.className = `custom-popup-icon ${type}`;
            
            switch (type) {
                case 'confirm':
                    iconText.textContent = '?';
                    break;
                case 'alert':
                    iconText.textContent = '!';
                    break;
                case 'success':
                    iconText.textContent = '✓';
                    break;
                default:
                    iconText.textContent = icon;
            }

            // Mettre à jour les boutons
            const buttonsContainer = document.getElementById('custom-popup-buttons');
            buttonsContainer.innerHTML = '';

            if (showCancel) {
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'custom-popup-btn secondary';
                cancelBtn.textContent = cancelText;
                cancelBtn.onclick = () => this.close(false);
                buttonsContainer.appendChild(cancelBtn);
            }

            const confirmBtn = document.createElement('button');
            confirmBtn.className = `custom-popup-btn ${confirmClass}`;
            confirmBtn.textContent = confirmText;
            confirmBtn.onclick = () => this.close(true);
            buttonsContainer.appendChild(confirmBtn);

            // Afficher la popup
            this.overlay.classList.add('show');
            
            // Focus sur le bouton de confirmation
            setTimeout(() => {
                confirmBtn.focus();
            }, 100);
        });
    }

    /**
     * Ferme la popup actuelle
     */
    close(result) {
        if (!this.overlay) return;
        
        this.overlay.classList.remove('show');
        this.isShowing = false;
        
        if (this.currentResolve) {
            this.currentResolve(result);
            this.currentResolve = null;
        }

        // Traiter la prochaine popup dans la queue
        setTimeout(() => {
            this.processQueue();
        }, 300); // Attendre la fin de l'animation
    }

    /**
     * Méthodes de convenance
     */
    confirm(message, title = 'Confirmation') {
        return this.show({
            type: 'confirm',
            title: title,
            message: message,
            confirmText: 'Confirmer',
            cancelText: 'Annuler',
            confirmClass: 'primary'
        });
    }

    alert(message, title = 'Information') {
        return this.show({
            type: 'alert',
            title: title,
            message: message,
            confirmText: 'OK',
            showCancel: false,
            confirmClass: 'primary'
        });
    }

    success(message, title = 'Succès') {
        return this.show({
            type: 'success',
            title: title,
            message: message,
            confirmText: 'OK',
            showCancel: false,
            confirmClass: 'success'
        });
    }

    danger(message, title = 'Attention') {
        return this.show({
            type: 'alert',
            title: title,
            message: message,
            confirmText: 'Supprimer',
            cancelText: 'Annuler',
            showCancel: true,
            confirmClass: 'danger'
        });
    }
}

// Initialiser le gestionnaire global
window.popupManager = new CustomPopupManager();
window.popupManager.init();

// Définir les fonctions globales de convenance
window.customConfirm = (message, title) => window.popupManager.confirm(message, title);
window.customAlert = (message, title) => window.popupManager.alert(message, title);
window.customSuccess = (message, title) => window.popupManager.success(message, title);
window.customDanger = (message, title) => window.popupManager.danger(message, title);