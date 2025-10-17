// Script pour la page proposer-film.php

// Fonction pour mettre à jour le compteur de caractères de la description
function updateCharCount() {
    const desc = document.getElementById("description");
    const counter = document.getElementById("charCount");
    
    if (desc.value.length > 400) {
        desc.value = desc.value.slice(0, 400);
    }

    counter.textContent = `${desc.value.length} / 400`;
    if (desc.value.length >= 390) {
        counter.style.color = "red";
    } else {
        counter.style.color = "";
    }
}

// Fonction pour mettre à jour les studios en fonction de la catégorie
function updateStudios() {
    const categorie = document.getElementById('categorie').value;
    const studioSelect = document.getElementById('studio');
    
    // Vider les options actuelles et ajouter "Sélectionnez un studio", "Autre" et "Inconnu"
    studioSelect.innerHTML = '<option value="">Sélectionnez un studio</option><option value="autre">Autre</option><option value="1">Inconnu</option>';
    
    if (categorie) {
        fetch('./scripts-php/get-studios.php?categorie=' + encodeURIComponent(categorie))
            .then(response => response.json())
            .then(data => {
                data.forEach(studio => {
                    // Éviter de dupliquer "Inconnu" qui est déjà ajouté manuellement
                    if (studio.nom !== 'Inconnu') {
                        const option = document.createElement('option');
                        option.value = studio.id;
                        option.textContent = studio.nom;
                        // Ajouter les studios après l'option "Inconnu"
                        studioSelect.appendChild(option);
                    }
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des studios:', error);
            });
    }
}

// Fonction pour afficher/masquer le champ "nouveau studio"
function toggleAutreStudio() {
    const studioSelect = document.getElementById('studio');
    const nouveauStudioInput = document.getElementById('nouveau_studio');
    
    if (studioSelect.value === 'autre') {
        nouveauStudioInput.style.display = 'block';
        nouveauStudioInput.required = true;
        nouveauStudioInput.classList.add('fade-in');
        setupAutocomplete(nouveauStudioInput, 'studios');
    } else {
        nouveauStudioInput.style.display = 'none';
        nouveauStudioInput.required = false;
        nouveauStudioInput.classList.remove('fade-in');
        removeAutocomplete(nouveauStudioInput);
    }
}

// Fonction pour configurer l'autocomplétion
function setupAutocomplete(input, type) {
    let timeout;
    let suggestionsList = document.getElementById(input.id + '_suggestions');
    
    // Créer la liste de suggestions si elle n'existe pas
    if (!suggestionsList) {
        suggestionsList = document.createElement('ul');
        suggestionsList.id = input.id + '_suggestions';
        suggestionsList.className = 'autocomplete-suggestions';
        
        // Créer un wrapper autocomplete-container si il n'existe pas déjà
        let container = input.parentElement.querySelector('.autocomplete-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'autocomplete-container';
            
            // Insérer le container avant l'input
            input.parentElement.insertBefore(container, input);
            // Déplacer l'input dans le container
            container.appendChild(input);
        }
        
        container.appendChild(suggestionsList);
    }
    
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsList.classList.remove('show');
            return;
        }
        
        timeout = setTimeout(() => {
            const categorie = document.getElementById('categorie').value;
            let url = `./scripts-php/get-autocomplete-${type}.php?search=${encodeURIComponent(query)}`;
            if (type === 'studios' && categorie) {
                url += `&categorie=${encodeURIComponent(categorie)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    suggestionsList.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            li.className = 'autocomplete-suggestion';
                            
                            li.addEventListener('click', function() {
                                input.value = this.textContent;
                                suggestionsList.classList.remove('show');
                            });
                            
                            suggestionsList.appendChild(li);
                        });
                        
                        suggestionsList.classList.add('show');
                    } else {
                        // Afficher un message "aucun résultat"
                        const li = document.createElement('li');
                        li.textContent = 'Aucun résultat trouvé';
                        li.className = 'autocomplete-no-results';
                        suggestionsList.appendChild(li);
                        suggestionsList.classList.add('show');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de l\'autocomplétion:', error);
                    suggestionsList.classList.remove('show');
                });
        }, 300);
    });
    
    // Masquer les suggestions quand on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsList.contains(e.target)) {
            suggestionsList.classList.remove('show');
        }
    });
}

// Fonction pour supprimer l'autocomplétion
function removeAutocomplete(input) {
    const suggestionsList = document.getElementById(input.id + '_suggestions');
    if (suggestionsList) {
        suggestionsList.classList.remove('show');
    }
}

// Fonction pour mettre à jour le label du nom de film selon la catégorie
function updateNomFilmLabel() {
    const categorie = document.getElementById("categorie").value;
    const label = document.getElementById("nom_film_label");
    const input = document.getElementById("nom_film");

    if (categorie === "Série" || categorie === "Série d'Animation") {
        label.textContent = "Nom de la série :";
        input.placeholder = "Nom de la série (max 50 caractères)";
    } else {
        label.textContent = "Nom du film :";
        input.placeholder = "Nom du film (max 50 caractères)";
    }
}

// Fonction pour gérer l'affichage des champs selon la catégorie
function handleCategorieChange() {
    const categorie = document.getElementById('categorie').value;
    const isSerie = categorie === "Série" || categorie === "Série d'Animation";

    // Éléments à gérer
    const ordreSuiteLabel = document.getElementById("ordre_suite_label");
    const ordreSuiteInput = document.getElementById("ordre_suite");
    const saisonLabel = document.getElementById("saison_label");
    const saisonInput = document.getElementById("saison");
    const nbrEpisodeLabel = document.getElementById("nbrEpisode_label");
    const nbrEpisodeInput = document.getElementById("nbrEpisode");

    if (isSerie) {
        // Masquer ordre suite
        ordreSuiteLabel.style.display = "none";
        ordreSuiteInput.style.display = "none";
        ordreSuiteInput.required = false;

        // Afficher saison et épisodes
        saisonLabel.style.display = "block";
        saisonInput.style.display = "block";
        saisonInput.required = true;
        saisonLabel.classList.add('fade-in');
        saisonInput.classList.add('fade-in');

        nbrEpisodeLabel.style.display = "block";
        nbrEpisodeInput.style.display = "block";
        nbrEpisodeInput.required = true;
        nbrEpisodeLabel.classList.add('fade-in');
        nbrEpisodeInput.classList.add('fade-in');
    } else {
        // Afficher ordre suite
        ordreSuiteLabel.style.display = "block";
        ordreSuiteInput.style.display = "block";
        ordreSuiteInput.required = false;

        // Masquer saison et épisodes
        saisonLabel.style.display = "none";
        saisonInput.style.display = "none";
        saisonInput.required = false;
        saisonLabel.classList.remove('fade-in');
        saisonInput.classList.remove('fade-in');

        nbrEpisodeLabel.style.display = "none";
        nbrEpisodeInput.style.display = "none";
        nbrEpisodeInput.required = false;
        nbrEpisodeLabel.classList.remove('fade-in');
        nbrEpisodeInput.classList.remove('fade-in');
    }

    // Mettre à jour le label du nom
    updateNomFilmLabel();
    
    // Mettre à jour les studios
    updateStudios();
}

// Fonction pour valider les sous-genres
function validateSousGenres() {
    const checkboxes = document.querySelectorAll('input[name="sous_genres[]"]:checked');
    const warning = document.getElementById('sous-genre-warning');
    
    if (checkboxes.length === 0) {
        warning.style.display = 'block';
        return false;
    } else {
        warning.style.display = 'none';
        return true;
    }
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = type;
    notification.style.display = 'block';
    
    // Masquer après 5 secondes
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

// Fonction pour gérer la soumission du formulaire
function handleFormSubmit(event) {
    event.preventDefault(); // Empêcher la soumission classique du formulaire
    
    // Valider les sous-genres
    if (!validateSousGenres()) {
        showNotification('Veuillez sélectionner au moins un sous-genre.', 'error');
        return false;
    }
    
    // Afficher un message de chargement
    const submitButton = document.getElementById('Bouton-proposer');
    const originalText = submitButton.value;
    submitButton.value = 'Proposition en cours...';
    submitButton.disabled = true;
    
    // Préparer les données du formulaire
    const form = document.getElementById('filmForm');
    const formData = new FormData(form);
    
    // Envoyer la requête AJAX
    fetch('./scripts-php/add-film-temp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurer le bouton
        submitButton.value = originalText;
        submitButton.disabled = false;
        
        if (data.success) {
            // Afficher le message de succès
            showNotification(data.success, 'success');
            // Réinitialiser le formulaire
            form.reset();
            updateCharCount(); // Remettre le compteur à zéro
            updateNomFilmLabel(); // Remettre le label par défaut
        } else if (data.error) {
            // Afficher le message d'erreur
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Restaurer le bouton
        submitButton.value = originalText;
        submitButton.disabled = false;
        // Afficher un message d'erreur générique
        showNotification('Une erreur est survenue lors de la soumission. Veuillez réessayer.', 'error');
    });
    
    return false;
}

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    // Initialiser le compteur de caractères
    updateCharCount();
    
    // Ajouter les écouteurs d'événements
    document.getElementById("description").addEventListener("input", updateCharCount);
    document.getElementById("categorie").addEventListener("change", handleCategorieChange);
    document.getElementById("studio").addEventListener("change", toggleAutreStudio);
    document.getElementById("auteur").addEventListener("change", function() {
        const nouveauAuteurInput = document.getElementById('nouveau_auteur');
        if (this.value === 'autre') {
            nouveauAuteurInput.style.display = 'block';
            nouveauAuteurInput.required = true;
            nouveauAuteurInput.classList.add('fade-in');
            setupAutocomplete(nouveauAuteurInput, 'auteurs');
        } else {
            nouveauAuteurInput.style.display = 'none';
            nouveauAuteurInput.required = false;
            nouveauAuteurInput.classList.remove('fade-in');
            removeAutocomplete(nouveauAuteurInput);
        }
    });
    
    // Ajouter la validation à la soumission du formulaire
    document.getElementById("filmForm").addEventListener("submit", handleFormSubmit);
    
    // Initialiser l'état des champs selon la catégorie par défaut
    updateNomFilmLabel();
});