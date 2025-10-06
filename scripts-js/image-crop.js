class ImageCropper {
    constructor() {
        this.modal = null;
        this.image = null;
        this.canvas = null;
        this.ctx = null;
        this.cropArea = null;
        this.isDragging = false;
        this.isResizing = false;
        this.dragStart = { x: 0, y: 0 };
        this.cropData = { x: 0, y: 0, width: 200, height: 200 };
        this.imageData = { width: 0, height: 0, naturalWidth: 0, naturalHeight: 0 };
        this.aspectRatio = 1; // 1:1 par défaut pour les photos de profil
        this.currentHandle = null;
        
        this.init();
    }
    
    init() {
        this.createModal();
        this.bindEvents();
    }
    
    createModal() {
        const modalHTML = `
            <div id="imageCropModal" class="image-crop-modal">
                <div class="image-crop-content">
                    <div class="crop-header">
                        <h3>Recadrer votre photo de profil</h3>
                        <button class="close-crop-modal" id="closeCropModal">&times;</button>
                    </div>
                    
                    <div class="crop-container" id="cropContainer">
                        <img id="cropPreview" class="crop-preview" alt="Aperçu">
                        <div id="cropArea" class="crop-area" style="display: none;">
                            <div class="crop-handle nw" data-handle="nw"></div>
                            <div class="crop-handle ne" data-handle="ne"></div>
                            <div class="crop-handle sw" data-handle="sw"></div>
                            <div class="crop-handle se" data-handle="se"></div>
                            <div class="crop-handle n" data-handle="n"></div>
                            <div class="crop-handle s" data-handle="s"></div>
                            <div class="crop-handle w" data-handle="w"></div>
                            <div class="crop-handle e" data-handle="e"></div>
                        </div>
                    </div>
                    
                    <div class="crop-controls">
                        <div class="crop-ratio-buttons">
                            <button class="ratio-btn active" data-ratio="1">Cercle</button>
                        </div>
                        <div class="crop-size-control">
                            <label for="cropSize">Taille du cercle:</label>
                            <input type="range" id="cropSize" min="50" max="400" value="200" class="size-slider">
                            <span id="sizeValue">200px</span>
                        </div>
                    </div>
                    
                    <div class="crop-info">
                        <p>Glissez pour déplacer la zone de recadrage, utilisez les poignées pour redimensionner</p>
                    </div>
                    
                    <div class="crop-actions">
                        <button class="crop-btn secondary" id="cancelCrop">Annuler</button>
                        <button class="crop-btn primary" id="applyCrop">Appliquer le recadrage</button>
                    </div>
                    
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('imageCropModal');
        this.cropArea = document.getElementById('cropArea');
        this.image = document.getElementById('cropPreview');
    }
    
    bindEvents() {
        // Fermeture de la modale
        document.getElementById('closeCropModal').addEventListener('click', () => this.closeModal());
        document.getElementById('cancelCrop').addEventListener('click', () => this.closeModal());
        
        // Clic en dehors de la modale
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });
        
        // Boutons de ratio (maintenant juste pour le cercle)
        document.querySelectorAll('.ratio-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.ratio-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.aspectRatio = 1; // Toujours 1:1 pour un cercle
                this.updateCropArea();
            });
        });
        
        // Contrôle de taille du cercle
        const sizeSlider = document.getElementById('cropSize');
        const sizeValue = document.getElementById('sizeValue');
        
        sizeSlider.addEventListener('input', (e) => {
            const newSize = parseInt(e.target.value);
            sizeValue.textContent = newSize + 'px';
            
            // Centrer le cercle avec la nouvelle taille
            const centerX = this.cropData.x + this.cropData.width / 2;
            const centerY = this.cropData.y + this.cropData.height / 2;
            
            this.cropData.width = newSize;
            this.cropData.height = newSize;
            this.cropData.x = centerX - newSize / 2;
            this.cropData.y = centerY - newSize / 2;
            
            this.updateCropArea();
        });
        
        // Application du recadrage
        document.getElementById('applyCrop').addEventListener('click', () => this.applyCrop());
        
        // Événements de la zone de recadrage
        this.cropArea.addEventListener('mousedown', (e) => this.startDrag(e));
        document.addEventListener('mousemove', (e) => this.drag(e));
        document.addEventListener('mouseup', () => this.endDrag());
        
        // Événements des poignées
        document.querySelectorAll('.crop-handle').forEach(handle => {
            handle.addEventListener('mousedown', (e) => this.startResize(e));
        });
        
        // Chargement de l'image
        this.image.addEventListener('load', () => this.onImageLoad());
    }
    
    openModal(file) {
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            this.image.src = e.target.result;
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        };
        reader.readAsDataURL(file);
    }
    
    closeModal() {
        this.modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        this.image.src = '';
        this.cropArea.style.display = 'none';
    }
    
    onImageLoad() {
        const container = document.getElementById('cropContainer');
        const containerRect = container.getBoundingClientRect();
        
        // Calculer les dimensions d'affichage de l'image
        this.imageData.naturalWidth = this.image.naturalWidth;
        this.imageData.naturalHeight = this.image.naturalHeight;
        this.imageData.width = this.image.offsetWidth;
        this.imageData.height = this.image.offsetHeight;
        
        // Initialiser la zone de recadrage circulaire au centre
        const size = Math.min(this.imageData.width, this.imageData.height) * 0.6;
        this.cropData = {
            x: (this.imageData.width - size) / 2,
            y: (this.imageData.height - size) / 2,
            width: size,
            height: size // Toujours carré pour un cercle
        };
        
        this.updateCropArea();
        this.cropArea.style.display = 'block';
    }
    
    updateCropArea() {
        // Pour un cercle, on maintient toujours un ratio 1:1
        if (this.cropData.width !== this.cropData.height) {
            const size = Math.min(this.cropData.width, this.cropData.height);
            this.cropData.width = size;
            this.cropData.height = size;
        }
        
        // Appliquer les limites
        this.cropData.x = Math.max(0, Math.min(this.cropData.x, this.imageData.width - this.cropData.width));
        this.cropData.y = Math.max(0, Math.min(this.cropData.y, this.imageData.height - this.cropData.height));
        this.cropData.width = Math.min(this.cropData.width, this.imageData.width - this.cropData.x);
        this.cropData.height = Math.min(this.cropData.height, this.imageData.height - this.cropData.y);
        
        // Maintenir la forme carrée après les ajustements de limites
        const finalSize = Math.min(this.cropData.width, this.cropData.height);
        this.cropData.width = finalSize;
        this.cropData.height = finalSize;
        
        // Mettre à jour l'affichage
        this.cropArea.style.left = this.cropData.x + 'px';
        this.cropArea.style.top = this.cropData.y + 'px';
        this.cropArea.style.width = this.cropData.width + 'px';
        this.cropArea.style.height = this.cropData.height + 'px';
    }
    
    startDrag(e) {
        if (e.target.classList.contains('crop-handle')) return;
        
        this.isDragging = true;
        const rect = this.cropArea.getBoundingClientRect();
        const containerRect = document.getElementById('cropContainer').getBoundingClientRect();
        
        this.dragStart = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
        
        e.preventDefault();
    }
    
    startResize(e) {
        this.isResizing = true;
        this.currentHandle = e.target.dataset.handle;
        this.dragStart = {
            x: e.clientX,
            y: e.clientY,
            cropX: this.cropData.x,
            cropY: this.cropData.y,
            cropWidth: this.cropData.width,
            cropHeight: this.cropData.height
        };
        
        e.preventDefault();
        e.stopPropagation();
    }
    
    drag(e) {
        if (this.isDragging) {
            const containerRect = document.getElementById('cropContainer').getBoundingClientRect();
            const newX = e.clientX - containerRect.left - this.dragStart.x;
            const newY = e.clientY - containerRect.top - this.dragStart.y;
            
            this.cropData.x = Math.max(0, Math.min(newX, this.imageData.width - this.cropData.width));
            this.cropData.y = Math.max(0, Math.min(newY, this.imageData.height - this.cropData.height));
            
            this.updateCropArea();
        } else if (this.isResizing) {
            this.handleResize(e);
        }
    }
    
    handleResize(e) {
        const deltaX = e.clientX - this.dragStart.x;
        const deltaY = e.clientY - this.dragStart.y;
        
        let newX = this.dragStart.cropX;
        let newY = this.dragStart.cropY;
        let newSize = Math.min(this.dragStart.cropWidth, this.dragStart.cropHeight);
        
        // Pour un cercle, on utilise la distance depuis le centre pour redimensionner
        const centerX = this.dragStart.cropX + this.dragStart.cropWidth / 2;
        const centerY = this.dragStart.cropY + this.dragStart.cropHeight / 2;
        const containerRect = document.getElementById('cropContainer').getBoundingClientRect();
        const mouseX = e.clientX - containerRect.left;
        const mouseY = e.clientY - containerRect.top;
        
        switch (this.currentHandle) {
            case 'se':
            case 'nw':
            case 'ne':
            case 'sw':
                // Calculer la nouvelle taille basée sur la distance depuis le centre
                const distance = Math.sqrt(Math.pow(mouseX - centerX, 2) + Math.pow(mouseY - centerY, 2));
                newSize = Math.max(50, Math.min(distance * 2, Math.min(this.imageData.width, this.imageData.height)));
                break;
            case 'n':
            case 's':
                newSize = Math.max(50, newSize + Math.abs(deltaY) * (deltaY > 0 ? 1 : -1));
                break;
            case 'w':
            case 'e':
                newSize = Math.max(50, newSize + Math.abs(deltaX) * (deltaX > 0 ? 1 : -1));
                break;
        }
        
        // Centrer la nouvelle zone de cadrage
        newX = centerX - newSize / 2;
        newY = centerY - newSize / 2;
        
        // Appliquer les limites
        newX = Math.max(0, Math.min(newX, this.imageData.width - newSize));
        newY = Math.max(0, Math.min(newY, this.imageData.height - newSize));
        
        // Si on dépasse les limites, ajuster la taille
        if (newX + newSize > this.imageData.width) {
            newSize = this.imageData.width - newX;
        }
        if (newY + newSize > this.imageData.height) {
            newSize = this.imageData.height - newY;
        }
        
        this.cropData = { x: newX, y: newY, width: newSize, height: newSize };
        this.updateCropArea();
    }
    
    endDrag() {
        this.isDragging = false;
        this.isResizing = false;
        this.currentHandle = null;
    }
    
    applyCrop() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'flex';
        
        // Calculer les coordonnées réelles sur l'image originale
        const scaleX = this.imageData.naturalWidth / this.imageData.width;
        const scaleY = this.imageData.naturalHeight / this.imageData.height;
        
        const realCrop = {
            x: this.cropData.x * scaleX,
            y: this.cropData.y * scaleY,
            width: this.cropData.width * scaleX,
            height: this.cropData.height * scaleY
        };
        
        // Créer un canvas pour le recadrage
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = realCrop.width;
        canvas.height = realCrop.height;
        
        // Préserver la transparence - ne pas remplir le canvas avec une couleur de fond
        // Le canvas est transparent par défaut
        
        // Dessiner l'image recadrée
        ctx.drawImage(
            this.image,
            realCrop.x, realCrop.y, realCrop.width, realCrop.height,
            0, 0, realCrop.width, realCrop.height
        );
        
        // Convertir en blob en préservant la transparence (PNG au lieu de JPEG)
        canvas.toBlob((blob) => {
            this.uploadCroppedImage(blob);
        }, 'image/png'); // PNG préserve la transparence, contrairement à JPEG
    }
    
    uploadCroppedImage(blob) {
        const formData = new FormData();
        // Utiliser PNG pour préserver la transparence
        formData.append('cropped_image', blob, 'profile.png');
        formData.append('upload_cropped_photo', '1');
        
        fetch('scripts-php/upload-cropped-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'none';
            
            if (data.success) {
                // Forcer l'actualisation de toutes les images de profil avec plusieurs techniques
                const timestamp = Date.now();
                
                // Sélecteurs plus complets pour toutes les images de profil possibles
                const profileSelectors = [
                    '.profile-photo',
                    '#profilImg', 
                    'img[src*="img-profile"]',
                    'img[src*="profil"]',
                    '.profil img',
                    '.profil-default',
                    '.profil-custom'
                ];
                
                profileSelectors.forEach(selector => {
                    const images = document.querySelectorAll(selector);
                    images.forEach(img => {
                        // Supprimer l'ancien timestamp et ajouter le nouveau
                        const baseSrc = img.src.split('?')[0];
                        // Utiliser plusieurs paramètres pour forcer le rafraîchissement
                        img.src = baseSrc + '?v=' + timestamp + '&cache=' + Math.random();
                        
                        // Forcer le rechargement de l'image
                        img.onload = function() {
                            // Déclencher un événement personnalisé pour notifier le changement
                            const event = new CustomEvent('profileImageUpdated', {
                                detail: { src: img.src, timestamp: timestamp }
                            });
                            document.dispatchEvent(event);
                        };
                        
                        // Gérer les erreurs de chargement
                        img.onerror = function() {
                            console.warn('Erreur lors du chargement de l\'image:', img.src);
                            // Réessayer après un court délai
                            setTimeout(() => {
                                img.src = baseSrc + '?v=' + (timestamp + 1) + '&retry=1';
                            }, 500);
                        };
                    });
                });
                
                // Mettre à jour également les attributs data-src si présents
                document.querySelectorAll('[data-src*="img-profile"], [data-src*="profil"]').forEach(element => {
                    const baseSrc = element.dataset.src.split('?')[0];
                    element.dataset.src = baseSrc + '?v=' + timestamp + '&cache=' + Math.random();
                });
                
                // Fermer la modale
                this.closeModal();
                
                // Afficher un message de succès
                this.showMessage('Photo de profil mise à jour avec succès !', 'success');
                
                // Pas de rechargement automatique - l'image est mise à jour via AJAX
            } else {
                this.showMessage(data.message || 'Erreur lors de l\'upload', 'error');
            }
        })
        .catch(error => {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'none';
            this.showMessage('Erreur de connexion', 'error');
            console.error('Error:', error);
        });
    }
    
    showMessage(message, type) {
        // Créer ou mettre à jour le message
        let messageDiv = document.querySelector('.crop-message');
        if (!messageDiv) {
            messageDiv = document.createElement('div');
            messageDiv.className = 'crop-message';
            document.querySelector('.image-crop-content').insertBefore(
                messageDiv, 
                document.querySelector('.crop-header').nextSibling
            );
        }
        
        messageDiv.textContent = message;
        messageDiv.className = `crop-message ${type}`;
        messageDiv.style.cssText = `
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            ${type === 'success' ? 
                'background: rgba(76, 175, 80, 0.2); color: #4CAF50; border: 1px solid #4CAF50;' : 
                'background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid #f44336;'
            }
        `;
        
        // Supprimer le message après 3 secondes
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }
}

// Initialiser le cropper quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    const imageCropper = new ImageCropper();
    
    // Modifier le comportement du bouton "Changer la photo"
    const photoUploadBtn = document.querySelector('.photo-upload-btn');
    const photoInput = document.getElementById('photo-input');
    
    if (photoUploadBtn && photoInput) {
        photoUploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            photoInput.click();
        });
        
        photoInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                imageCropper.openModal(this.files[0]);
                // Réinitialiser l'input pour permettre de sélectionner le même fichier
                this.value = '';
            }
        });
    }
});