<?php
// Script PHP pour le bouton "retour en haut" flottant
// À inclure en bas de page avec : <?php include './scroll-to-top.php'; ?>

<style>
/* Styles pour le bouton "retour en haut" flottant */
.scroll-to-top {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
    border: 2px solid #ff8c00;
    border-radius: 50%;
    cursor: pointer;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(255, 140, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.scroll-to-top:hover {
    background: linear-gradient(135deg, #3c3c3c 0%, #2a2a2a 100%);
    border-color: #ff7700;
    box-shadow: 0 6px 20px rgba(255, 140, 0, 0.3);
    transform: translateY(-2px) scale(1.05);
}

.scroll-to-top:active {
    transform: translateY(0) scale(0.95);
    box-shadow: 0 2px 10px rgba(255, 107, 53, 0.3);
}

.scroll-to-top .arrow {
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 12px solid #ff8c00;
    transition: border-bottom-color 0.3s ease;
}

.scroll-to-top:hover .arrow {
    border-bottom-color: #ff7700;
}

/* Animation de pulsation subtile */
@keyframes pulse {
    0% {
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
    }
    50% {
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }
    100% {
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
    }
}

.scroll-to-top.pulse {
    animation: pulse 2s infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .scroll-to-top {
        width: 45px;
        height: 45px;
        top: 15px;
        right: 15px;
    }
    
    .scroll-to-top .arrow {
        border-left-width: 6px;
        border-right-width: 6px;
        border-bottom-width: 10px;
    }
}

@media (max-width: 480px) {
    .scroll-to-top {
        width: 40px;
        height: 40px;
        top: 10px;
        right: 10px;
    }
    
    .scroll-to-top .arrow {
        border-left-width: 5px;
        border-right-width: 5px;
        border-bottom-width: 8px;
    }
}
</style>

<!-- Bouton "retour en haut" -->
<div class="scroll-to-top" id="scrollToTopBtn" title="Retour en haut">
    <div class="arrow"></div>
</div>

<script>
// Gestionnaire du bouton "retour en haut"
class ScrollToTopManager {
    constructor() {
        this.button = document.getElementById('scrollToTopBtn');
        this.header = document.querySelector('header, .header, nav, .nav');
        this.isVisible = false;
        this.scrollThreshold = 100; // Seuil minimum de scroll
        
        this.init();
    }
    
    init() {
        if (!this.button) return;
        
        // Écouter le scroll
        window.addEventListener('scroll', this.handleScroll.bind(this));
        
        // Écouter le clic sur le bouton
        this.button.addEventListener('click', this.scrollToTop.bind(this));
        
        // Vérification initiale
        this.handleScroll();
    }
    
    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const shouldShow = this.shouldShowButton(scrollTop);
        
        if (shouldShow && !this.isVisible) {
            this.showButton();
        } else if (!shouldShow && this.isVisible) {
            this.hideButton();
        }
    }
    
    shouldShowButton(scrollTop) {
        // Vérifier si on a scrollé suffisamment
        if (scrollTop < this.scrollThreshold) {
            return false;
        }
        
        // Si on trouve un header, vérifier s'il est visible
        if (this.header) {
            const headerRect = this.header.getBoundingClientRect();
            return headerRect.bottom < 0; // Header complètement hors de vue
        }
        
        // Sinon, se baser uniquement sur le seuil de scroll
        return true;
    }
    
    showButton() {
        this.button.classList.add('visible');
        this.isVisible = true;
        
        // Ajouter l'animation de pulsation après un délai
        setTimeout(() => {
            if (this.isVisible) {
                this.button.classList.add('pulse');
            }
        }, 1000);
    }
    
    hideButton() {
        this.button.classList.remove('visible', 'pulse');
        this.isVisible = false;
    }
    
    scrollToTop() {
        // Animation de scroll fluide vers le haut
        const scrollStep = -window.scrollY / (500 / 15); // 500ms d'animation
        
        const scrollAnimation = () => {
            if (window.scrollY !== 0) {
                window.scrollBy(0, scrollStep);
                requestAnimationFrame(scrollAnimation);
            }
        };
        
        requestAnimationFrame(scrollAnimation);
        
        // Alternative avec scrollTo pour les navigateurs modernes
        if ('scrollBehavior' in document.documentElement.style) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    }
}

// Initialiser le gestionnaire quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    window.scrollToTopManager = new ScrollToTopManager();
});

// Fonction globale pour forcer la mise à jour (utile si le header change dynamiquement)
function updateScrollToTopButton() {
    if (window.scrollToTopManager) {
        window.scrollToTopManager.handleScroll();
    }
}
</script>