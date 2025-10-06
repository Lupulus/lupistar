<?php
// popup.php - Système de popup personnalisé pour Wolf Film
?>

<style>
/* Styles pour les popups personnalisées */
.custom-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.custom-popup-overlay.show {
    opacity: 1;
    visibility: visible;
}

.custom-popup {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border-radius: 15px;
    padding: 30px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    border: 1px solid #444;
    transform: scale(0.7) translateY(-50px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.custom-popup-overlay.show .custom-popup {
    transform: scale(1) translateY(0);
}

.custom-popup::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ff6b35, #f7931e, #ffd23f);
}

.custom-popup-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.custom-popup-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
    font-weight: bold;
}

.custom-popup-icon.confirm {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
}

.custom-popup-icon.alert {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.custom-popup-icon.success {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.custom-popup-title {
    color: #fff;
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    flex: 1;
}

.custom-popup-message {
    color: #ccc;
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 25px;
    text-align: left;
}

.custom-popup-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.custom-popup-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 80px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.custom-popup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.custom-popup-btn.primary {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
}

.custom-popup-btn.primary:hover {
    background: linear-gradient(135deg, #e55a2b, #e8841a);
}

.custom-popup-btn.secondary {
    background: #444;
    color: #ccc;
    border: 1px solid #666;
}

.custom-popup-btn.secondary:hover {
    background: #555;
    color: #fff;
    border-color: #777;
}

.custom-popup-btn.danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.custom-popup-btn.danger:hover {
    background: linear-gradient(135deg, #d62c1a, #a93226);
}

.custom-popup-btn.success {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.custom-popup-btn.success:hover {
    background: linear-gradient(135deg, #229954, #28b463);
}

/* Animation d'entrée */
@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: scale(0.7) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Animation de sortie */
@keyframes popupSlideOut {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.7) translateY(-50px);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .custom-popup {
        margin: 20px;
        padding: 25px;
        max-width: none;
        width: calc(100% - 40px);
    }
    
    .custom-popup-buttons {
        flex-direction: column;
    }
    
    .custom-popup-btn {
        width: 100%;
        margin-bottom: 8px;
    }
    
    .custom-popup-btn:last-child {
        margin-bottom: 0;
    }
}

/* Accessibilité */
.custom-popup-btn:focus {
    outline: 2px solid #ff6b35;
    outline-offset: 2px;
}

.custom-popup-overlay:focus {
    outline: none;
}

/* Animation pour le backdrop */
.custom-popup-overlay {
    backdrop-filter: blur(3px);
}
</style>

<!-- Structure HTML pour les popups -->
<div id="custom-popup-overlay" class="custom-popup-overlay">
    <div class="custom-popup" role="dialog" aria-modal="true">
        <div class="custom-popup-header">
            <div id="custom-popup-icon" class="custom-popup-icon">
                <span id="custom-popup-icon-text">?</span>
            </div>
            <h3 id="custom-popup-title" class="custom-popup-title">Confirmation</h3>
        </div>
        <div id="custom-popup-message" class="custom-popup-message">
            Êtes-vous sûr de vouloir effectuer cette action ?
        </div>
        <div id="custom-popup-buttons" class="custom-popup-buttons">
            <button id="custom-popup-cancel" class="custom-popup-btn secondary">Annuler</button>
            <button id="custom-popup-confirm" class="custom-popup-btn primary">Confirmer</button>
        </div>
    </div>
</div>

<script>
// Attendre que le gestionnaire de popup soit chargé depuis custom-popup.js
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le gestionnaire est disponible
    if (typeof window.popupManager !== 'undefined') {
        // Définir les fonctions globales de convenance
        window.customConfirm = (message, title) => window.popupManager.confirm(message, title);
        window.customAlert = (message, title) => window.popupManager.alert(message, title);
        window.customSuccess = (message, title) => window.popupManager.success(message, title);
        window.customDanger = (message, title) => window.popupManager.danger(message, title);
    }
});
</script>