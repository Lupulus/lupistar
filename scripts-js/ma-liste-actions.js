// Script pour gérer les actions spécifiques à ma-liste.php

// Fonction pour retirer un film de la liste personnelle
function retirerDeListe(filmId, filmName) {
    if (!confirm(`Êtes-vous sûr de vouloir retirer "${filmName}" de votre liste ?`)) {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "./scripts-php/remove-from-list.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Retirer l'élément du DOM
                    const filmBox = document.querySelector(`[data-id="${filmId}"]`);
                    if (filmBox) {
                        filmBox.remove();
                    }
                    
                    // Vérifier s'il reste des films affichés
                    const remainingFilms = document.querySelectorAll('.film-box').length;
                    if (remainingFilms === 0) {
                        // Recharger la page pour afficher le message "aucun film"
                        rechercherFilms();
                    }
                    
                    // Afficher un message de succès
                    showNotification(`"${filmName}" a été retiré de votre liste.`, 'success');
                } else {
                    showNotification(response.message || 'Erreur lors de la suppression du film.', 'error');
                }
            } catch (e) {
                console.error('Erreur lors du parsing JSON:', e);
                showNotification('Erreur lors de la suppression du film.', 'error');
            }
        } else {
            showNotification('Erreur de connexion au serveur.', 'error');
        }
    };
    
    xhr.onerror = function() {
        showNotification('Erreur de connexion au serveur.', 'error');
    };
    
    xhr.send(`film_id=${encodeURIComponent(filmId)}`);
}

// Fonction pour afficher des notifications
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Styles inline pour la notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: opacity 0.3s ease;
    `;
    
    // Couleurs selon le type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            notification.style.backgroundColor = '#f44336';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ff9800';
            break;
        default:
            notification.style.backgroundColor = '#2196F3';
    }
    
    // Ajouter au DOM
    document.body.appendChild(notification);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Fonction pour changer de page (pagination)
function changerPage(page) {
    const activeTab = document.querySelector('.tablinks.active');
    const category = activeTab ? activeTab.textContent : 'Animation';
    
    // Récupérer les filtres actuels
    const input = document.getElementById("search-bar").value;
    const studio = document.getElementById("studio-filter").value;
    const annee = document.getElementById("annee-filter").value;
    const note = document.getElementById("note-filter").value;
    const pays = document.getElementById("pays-filter").value;
    const type = document.getElementById("type-filter").value;
    const episodes = document.getElementById("episodes-filter").value;

    const xhr = new XMLHttpRequest();
    xhr.open("GET", "./scripts-php/ma-liste-display/display.php?categorie=" + encodeURIComponent(category) +
             "&recherche=" + encodeURIComponent(input) +
             "&studio=" + encodeURIComponent(studio) +
             "&annee=" + encodeURIComponent(annee) +
             "&note=" + encodeURIComponent(note) +
             "&pays=" + encodeURIComponent(pays) +
             "&type=" + encodeURIComponent(type) +
             "&episodes=" + encodeURIComponent(episodes) +
             "&page=" + encodeURIComponent(page));
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById("tabcontent").innerHTML = xhr.responseText;
            
            // Réattacher les événements aux nouveaux boutons
            attachRemoveButtonEvents();
        }
    };
    xhr.send();

    // Mettre à jour l'URL
    history.pushState(null, '', '?categorie=' + encodeURIComponent(category) +
                                '&recherche=' + encodeURIComponent(input) +
                                '&studio=' + encodeURIComponent(studio) +
                                '&annee=' + encodeURIComponent(annee) +
                                '&note=' + encodeURIComponent(note) +
                                '&pays=' + encodeURIComponent(pays) +
                                '&type=' + encodeURIComponent(type) +
                                '&episodes=' + encodeURIComponent(episodes) +
                                '&page=' + encodeURIComponent(page));
}

// Fonction pour attacher les événements aux boutons "Retirer de ma liste"
function attachRemoveButtonEvents() {
    const removeButtons = document.querySelectorAll('.remove-from-list-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filmId = this.getAttribute('data-film-id');
            const filmName = this.getAttribute('data-film-name');
            retirerDeListe(filmId, filmName);
        });
    });
}

// Attacher les événements au chargement initial
document.addEventListener('DOMContentLoaded', function() {
    attachRemoveButtonEvents();
});

// Réattacher les événements après chaque mise à jour du contenu
document.addEventListener('contentUpdated', function() {
    attachRemoveButtonEvents();
});