document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("film-modal");
    const modalContent = document.getElementById("modal-content");

    // Fermer la modal en cliquant à l'extérieur
    modal.addEventListener("click", function (event) {
        if (!modalContent.contains(event.target)) {
            modal.style.opacity = "0";
            setTimeout(() => { modal.style.display = "none"; modal.style.opacity = "1"; }, 300);
        }
    });

    // Fonction pour attacher les événements aux film-box
    function attachFilmBoxEvents() {
        document.querySelectorAll(".film-box").forEach(film => {
            // Supprimer les anciens événements pour éviter les doublons
            film.removeEventListener("click", handleFilmBoxClick);
            film.addEventListener("click", handleFilmBoxClick);
        });
    }

    // Gestionnaire de clic pour les film-box
    function handleFilmBoxClick(event) {
        const filmId = this.getAttribute("data-id");

        fetch(`./scripts-php/film-details-modal.php?id=${filmId}`)
            .then(response => response.text())
            .then(html => {
                modalContent.innerHTML = html;
                modal.style.display = "flex";

                setTimeout(() => {
                    const closeButton = modalContent.querySelector(".modal-close");
                    if (closeButton) {
                        closeButton.addEventListener("click", () => {
                            modal.style.display = "none";
                        });
                    } else {
                        console.error("⚠️ Bouton .modal-close non trouvé après l'injection !");
                    }

                    // Activer la gestion de l'icône favori
                    setupFavoriteToggle();
                    // Activer la gestion de la modification de note
                    setupNoteEditing();
                    // Charger le graphique dès l'ouverture du modal
                    loadNoteGraph(filmId); 
                }, 50);
            })
            .catch(error => {
                console.error("❌ Erreur lors de la récupération des détails du film :", error);
                alert("Impossible de récupérer les détails du film.");
            });
    }

    // Attacher les événements au chargement initial
    attachFilmBoxEvents();

    // Observer les changements dans le DOM pour réattacher les événements après les requêtes AJAX
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Vérifier si des film-box ont été ajoutés
                const hasFilmBox = Array.from(mutation.addedNodes).some(node => 
                    node.nodeType === 1 && (node.classList.contains('film-box') || node.querySelector('.film-box'))
                );
                if (hasFilmBox) {
                    attachFilmBoxEvents();
                }
            }
        });
    });

    // Observer les changements dans le conteneur des films
    const tabContent = document.getElementById('tabcontent');
    if (tabContent) {
        observer.observe(tabContent, { childList: true, subtree: true });
    }

    function setupFavoriteToggle() {
        const favoriteButton = document.querySelector(".wolf-view");
        if (favoriteButton) {
            favoriteButton.addEventListener("click", function () {
                const filmId = this.getAttribute("data-id");
                const action = this.getAttribute("data-action");
                const nomFilm = this.getAttribute("data-nom");

                fetch("./scripts-php/action-perso-liste.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `film_id=${filmId}&nom_film=${encodeURIComponent(nomFilm)}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (action === "add") {
                            this.classList.add("invert-filter");
                            this.setAttribute("data-action", "remove");
                            this.title = "Supprimer de Ma Liste";
                            // Afficher dynamiquement le cadre de notation
                            displayUserNoteSection(filmId);
                        } else {
                            this.classList.remove("invert-filter");
                            this.setAttribute("data-action", "add");
                            this.title = "Ajouter à Ma Liste !";
                            // Supprimer le cadre de notation
                            removeUserNoteSection();
                        }
                    } else {
                        alert("Erreur : " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erreur lors de la mise à jour de la liste :", error);
                    alert("Erreur lors de la communication avec le serveur.");
                });
            });
        }
    }

    function displayUserNoteSection(filmId) {
        const userNoteContainer = document.querySelector(".user-note-container");

        if (!userNoteContainer) {
            const newNoteContainer = document.createElement("div");
            newNoteContainer.className = "user-note-container";
            newNoteContainer.innerHTML = `
                <p><strong>Ma note :</strong> 
                    <span id="user-note">Non noté</span>
                    <span id="edit-note" class="edit-icon" title="Modifier ma note">✏️</span>
                </p>
                <input type="number" id="note-input" min="0" max="10" step="0.25" style="display:none;">
            `;
            document.querySelector(".modal-left").appendChild(newNoteContainer);
            setupNoteEditing();
        }
    }

    function removeUserNoteSection() {
        const userNoteContainer = document.querySelector(".user-note-container");
        if (userNoteContainer) {
            userNoteContainer.remove();
        }
    }

    function setupNoteEditing() {
        const editNote = document.getElementById("edit-note");
        const userNote = document.getElementById("user-note");
        const noteInput = document.getElementById("note-input");

        if (editNote && userNote && noteInput) {
            editNote.addEventListener("click", function () {
                noteInput.value = parseFloat(userNote.textContent) || "";
                userNote.style.display = "none";
                editNote.style.display = "none";
                noteInput.style.display = "inline";
                noteInput.focus();
            });

            noteInput.addEventListener("blur", function () {
                saveUserNote();
            });

            noteInput.addEventListener("keypress", function (event) {
                if (event.key === "Enter") {
                    saveUserNote();
                }
            });

            function saveUserNote() {
                const modalContent = document.querySelector(".modal-content");
                if (!modalContent) {
                    console.error("⚠️ Erreur : .modal-content introuvable !");
                    return;
                }
            
                const filmId = modalContent.getAttribute("data-id");
                if (!filmId || isNaN(filmId) || parseInt(filmId) === 0) {
                    console.error("⚠️ Erreur : film_id invalide :", filmId);
                    alert("Impossible de sauvegarder la note, l'ID du film est invalide.");
                    return;
                }
            
                const newNote = parseFloat(document.getElementById("note-input").value);
                if (newNote < 0 || newNote > 10 || isNaN(newNote)) {
                    alert("Veuillez entrer une note valide entre 0 et 10.");
                    return;
                }
            
                fetch("./scripts-php/update-note.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `film_id=${filmId}&note=${newNote}`
                })
                .then(response => response.text())
                .then(responseText => {
                    console.log("🔹 Réponse brute du serveur :", responseText);
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        console.error("❌ Erreur lors du parsing JSON :", error);
                        alert("Erreur de réponse du serveur.");
                        return;
                    }
            
                    if (data.success) {
                        document.getElementById("user-note").textContent = `${newNote}/10`;
                        document.getElementById("user-note").style.display = "inline";
                        document.getElementById("edit-note").style.display = "inline";
                        document.getElementById("note-input").style.display = "none";

                        // Mettre à jour les étoiles dans le film-box correspondant
                        updateFilmBoxStars(filmId, data.nouvelle_note_moyenne);

                        //Mettre à jour le graphique
                        updateNoteGraph(filmId);
                    } else {
                        alert("Erreur lors de la mise à jour de la note.");
                    }
                })
                .catch(error => {
                    console.error("❌ Erreur AJAX :", error);
                });
            }
        }
    }

    // Fonction pour mettre à jour les étoiles dans le film-box
    function updateFilmBoxStars(filmId, nouvelleNoteMoyenne) {
        const filmBox = document.querySelector(`.film-box[data-id="${filmId}"]`);
        if (!filmBox) {
            console.log("Film-box non trouvé pour l'ID:", filmId);
            return;
        }

        const noteValue = filmBox.querySelector('.note-value');
        const stars = filmBox.querySelectorAll('.star');
        
        if (noteValue) {
            noteValue.textContent = parseFloat(nouvelleNoteMoyenne).toFixed(1);
        }

        if (stars.length > 0) {
            const starsToFill = Math.floor(nouvelleNoteMoyenne);
            
            stars.forEach((star, index) => {
                if (index < starsToFill) {
                    star.classList.add('filled');
                } else {
                    star.classList.remove('filled');
                }
            });
        }

        console.log(`✅ Étoiles mises à jour pour le film ${filmId}: ${nouvelleNoteMoyenne}/10`);
    }

    function updateNoteGraph(filmId) {
        fetch(`./scripts-php/get-film-notes.php?id=${filmId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const votesParIntervalle = data.votes_par_intervalle;
                const maxVotes = Math.max(...Object.values(votesParIntervalle), 1); // Pour éviter division par 0
                
                // Sélection des barres et mise à jour de leur hauteur et couleur
                document.querySelectorAll(".note-bar-wrapper").forEach((barWrapper, index) => {
                    const interval = Object.keys(votesParIntervalle)[index];
                    const votes = votesParIntervalle[interval] || 0;

                    const bar = barWrapper.querySelector(".note-bar");
                    const count = barWrapper.querySelector(".note-count");

                    // Mise à jour de la hauteur
                    const barHeight = (votes > 0) ? (90 * votes / maxVotes) : 5;
                    bar.style.height = `${barHeight}px`;

                    // Mise à jour de la couleur
                    const color = `rgb(${255 - (votes * 255 / maxVotes)}, ${votes * 255 / maxVotes}, 50)`;
                    bar.style.backgroundColor = color;

                    // Mise à jour du nombre de votes
                    count.textContent = votes;
                });

                console.log("🔄 Graphique mis à jour !");
            } else {
                console.error("⚠️ Erreur lors de la mise à jour du graphique :", data.error);
            }
        })
        .catch(error => {
            console.error("❌ Erreur AJAX lors de la mise à jour du graphique :", error);
        });
    }

    function loadNoteGraph(filmId) {
        fetch(`./scripts-php/get-film-notes.php?id=${filmId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const votesParIntervalle = data.votes_par_intervalle;
                const maxVotes = Math.max(...Object.values(votesParIntervalle), 1); // Pour éviter division par 0
                
                const noteBarChart = document.getElementById("note-bar-chart");
                if (noteBarChart) {
                    noteBarChart.innerHTML = ""; // Vider le contenu avant de mettre à jour
                    
                    Object.keys(votesParIntervalle).forEach(interval => {
                        const votes = votesParIntervalle[interval] || 0;

                        const barWrapper = document.createElement("div");
                        barWrapper.className = "note-bar-wrapper";

                        const count = document.createElement("span");
                        count.className = "note-count";
                        count.textContent = votes;

                        const bar = document.createElement("div");
                        bar.className = "note-bar";
                        bar.style.height = `${(votes > 0) ? (90 * votes / maxVotes) : 5}px`;
                        bar.style.backgroundColor = `rgb(${255 - (votes * 255 / maxVotes)}, ${votes * 255 / maxVotes}, 50)`;

                        const label = document.createElement("span");
                        label.className = "note-label";
                        label.textContent = interval;

                        barWrapper.appendChild(count);
                        barWrapper.appendChild(bar);
                        barWrapper.appendChild(label);

                        noteBarChart.appendChild(barWrapper);
                    });

                    console.log("🔄 Graphique chargé dès l'ouverture !");
                } else {
                    console.error("⚠️ Élément note-bar-chart non trouvé");
                }
            } else {
                console.error("⚠️ Erreur lors du chargement des notes :", data.error);
            }
        })
        .catch(error => {
            console.error("❌ Erreur AJAX lors du chargement des notes :", error);
        });
    }
});