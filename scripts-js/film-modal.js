document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("film-modal");
    const modalContent = document.getElementById("modal-content");

    modal.addEventListener("click", function (event) {
        if (!modalContent.contains(event.target)) {
            modal.style.opacity = "0";
            setTimeout(() => { modal.style.display = "none"; modal.style.opacity = "1"; }, 300);
        }
    });    

    document.querySelectorAll(".recent-film-item").forEach(film => {
        film.addEventListener("click", () => {
            const filmId = film.getAttribute("data-id");

            fetch(`./scripts-php/film-details-modal.php?id=${filmId}`)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                    modal.style.display = "flex";

                    //console.log("Contenu modal injecté avec succès !");

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
                        // Charger le graphique dès l’ouverture du modal
                        loadNoteGraph(filmId); 
                    }, 50);
                })
                .catch(error => {
                    console.error("❌ Erreur lors de la récupération des détails du film :", error);
                    alert("Impossible de récupérer les détails du film.");
                });
        });
    });

    function setupFavoriteToggle() {
        const favoriteButton = document.querySelector(".wolf-view");

        if (favoriteButton) {
            favoriteButton.addEventListener("click", function () {
                const filmId = this.getAttribute("data-id");
                const filmName = encodeURIComponent(this.getAttribute("data-nom"));
                const action = this.getAttribute("data-action");

                fetch(`./scripts-php/action-perso-liste.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `film_id=${filmId}&nom_film=${filmName}&action=${action}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        if (!text) {
                            throw new Error('Empty response from server');
                        }
                        return JSON.parse(text);
                    });
                })
                .then(data => {
                    console.log("Réponse du serveur :", data);

                    if (data.success) {
                        if (data.message.includes("ajouté")) {
                            // Mise à jour de l'icône et affichage de "Ma note"
                            this.setAttribute("data-action", "remove");
                            this.classList.add("invert-filter");
                            displayUserNoteSection(filmId);
                        } else if (data.message.includes("supprimé")) {
                            // Mise à jour de l'icône et suppression de "Ma note"
                            this.setAttribute("data-action", "add");
                            this.classList.remove("invert-filter");
                            removeUserNoteSection();
                        }
                    } else {
                        alert("Erreur: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erreur AJAX :", error);
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

                        // Mettre à jour les étoiles dans le recent-film-item correspondant (pour index.php)
                        updateRecentFilmStars(filmId, data.nouvelle_note_moyenne);

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
        }
    }
    function loadNoteGraph(filmId) {
        fetch(`./scripts-php/get-film-notes.php?id=${filmId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const votesParIntervalle = data.votes_par_intervalle;
                const maxVotes = Math.max(...Object.values(votesParIntervalle), 1); // Pour éviter division par 0
                
                const noteBarChart = document.getElementById("note-bar-chart");
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
                console.error("⚠️ Erreur lors du chargement des notes :", data.error);
            }
        })
        .catch(error => {
            console.error("❌ Erreur AJAX lors du chargement des notes :", error);
        });
    }
});