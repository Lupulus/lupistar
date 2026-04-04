document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('film-modal');
    const modalContent = document.getElementById('modal-content');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!modal || !modalContent) return;

    const closeModal = () => {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.opacity = '1';
            modalContent.innerHTML = '';
            modalContent.removeAttribute('data-id');
        }, 300);
    };

    const postForm = (url, body) => {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body,
        });
    };

    const updateRecentFilmStars = (filmId, nouvelleNoteMoyenne) => {
        const value = nouvelleNoteMoyenne === null || nouvelleNoteMoyenne === undefined ? '-' : nouvelleNoteMoyenne;

        const recentItem = document.querySelector(`.recent-film-item[data-id="${filmId}"]`);
        if (recentItem) {
            const starsP = recentItem.querySelector('.stars');
            if (starsP) {
                const strong = starsP.querySelector('strong');
                starsP.innerHTML = `${strong ? strong.outerHTML : ''}${value}/10`;
            }
        }

        const filmBox = document.querySelector(`.film-box[data-id="${filmId}"]`);
        if (filmBox) {
            const noteValue = filmBox.querySelector('.note-value');
            if (noteValue) {
                noteValue.textContent = isNaN(value) ? String(value) : parseFloat(value).toFixed(1);
            }

            const stars = filmBox.querySelectorAll('.star');
            if (stars.length > 0) {
                const starsToFill = isNaN(value) ? 0 : Math.floor(parseFloat(value));
                stars.forEach((s, idx) => {
                    if (idx < starsToFill) s.classList.add('filled');
                    else s.classList.remove('filled');
                });
            }
        }
    };

    const removeUserNoteSection = () => {
        const userNoteContainer = document.querySelector('.user-note-container');
        if (userNoteContainer) {
            userNoteContainer.remove();
        }
    };

    const displayUserNoteSection = () => {
        const userNoteContainer = document.querySelector('.user-note-container');
        if (userNoteContainer) return;

        const modalLeft = document.querySelector('.modal-left');
        if (!modalLeft) return;

        const newNoteContainer = document.createElement('div');
        newNoteContainer.className = 'user-note-container';
        newNoteContainer.innerHTML = `
            <p><strong>Ma note :</strong> 
                <span id="user-note">Non noté</span>
                <span id="edit-note" class="edit-icon" title="Modifier ma note">✏️</span>
            </p>
            <input type="number" id="note-input" min="0" max="10" step="0.25" style="display:none;">
        `;

        modalLeft.appendChild(newNoteContainer);
        setupNoteEditing();
    };

    const setupFavoriteToggle = () => {
        const favoriteButton = modalContent.querySelector('.wolf-view');
        if (!favoriteButton) return;

        favoriteButton.addEventListener('click', function () {
            const filmId = this.getAttribute('data-id');
            const action = this.getAttribute('data-action');

            postForm(`/films/${filmId}/personal-list`, `action=${encodeURIComponent(action)}`)
                .then(async (response) => {
                    const text = await response.text();
                    if (!response.ok) {
                        throw new Error(text || `HTTP error! status: ${response.status}`);
                    }
                    return JSON.parse(text);
                })
                .then((data) => {
                    if (!data.success) {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                        return;
                    }

                    if ((data.message || '').includes('ajouté')) {
                        this.setAttribute('data-action', 'remove');
                        this.classList.add('invert-filter');
                        displayUserNoteSection();
                        loadNoteGraph(filmId);
                    } else if ((data.message || '').includes('supprimé')) {
                        this.setAttribute('data-action', 'add');
                        this.classList.remove('invert-filter');
                        removeUserNoteSection();
                    }

                    document.querySelectorAll(`.film-box img.wolf-view[data-id="${filmId}"]`).forEach((el) => {
                        if ((data.message || '').includes('ajouté')) {
                            el.setAttribute('data-action', 'remove');
                            el.classList.add('invert-filter');
                            el.setAttribute('title', 'Supprimer de Ma Liste');
                        } else if ((data.message || '').includes('supprimé')) {
                            el.setAttribute('data-action', 'add');
                            el.classList.remove('invert-filter');
                            el.setAttribute('title', 'Ajouter à Ma Liste !');
                        }
                    });

                    updateRecentFilmStars(filmId, data.nouvelle_note_moyenne);
                })
                .catch((error) => {
                    console.error('Erreur AJAX :', error);
                    alert('Erreur lors de la communication avec le serveur.');
                });
        });
    };

    const setupNoteEditing = () => {
        const editNote = document.getElementById('edit-note');
        const userNote = document.getElementById('user-note');
        const noteInput = document.getElementById('note-input');

        if (!editNote || !userNote || !noteInput) return;

        editNote.addEventListener('click', function () {
            const raw = (userNote.textContent || '').replace('/10', '').trim();
            noteInput.value = parseFloat(raw) || '';
            userNote.style.display = 'none';
            editNote.style.display = 'none';
            noteInput.style.display = 'inline';
            noteInput.focus();
        });

        const saveUserNote = () => {
            const filmId = modalContent.getAttribute('data-id');
            if (!filmId || isNaN(filmId) || parseInt(filmId, 10) === 0) {
                alert("Impossible de sauvegarder la note, l'ID du film est invalide.");
                return;
            }

            const newNote = parseFloat(noteInput.value);
            if (newNote < 0 || newNote > 10 || isNaN(newNote)) {
                alert('Veuillez entrer une note valide entre 0 et 10.');
                return;
            }

            postForm(`/films/${filmId}/note`, `note=${encodeURIComponent(newNote)}`)
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        alert('Erreur lors de la mise à jour de la note.');
                        return;
                    }

                    userNote.textContent = `${newNote}/10`;
                    userNote.style.display = 'inline';
                    editNote.style.display = 'inline';
                    noteInput.style.display = 'none';

                    updateRecentFilmStars(filmId, data.nouvelle_note_moyenne);
                    updateNoteGraph(filmId);
                })
                .catch((error) => {
                    console.error('❌ Erreur AJAX :', error);
                });
        };

        noteInput.addEventListener('blur', saveUserNote);
        noteInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                saveUserNote();
            }
        });
    };

    const updateNoteGraph = (filmId) => {
        fetch(`/films/${filmId}/notes`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) return;

                const votesParIntervalle = data.votes_par_intervalle;
                const maxVotes = Math.max(...Object.values(votesParIntervalle), 1);

                document.querySelectorAll('.note-bar-wrapper').forEach((barWrapper, index) => {
                    const interval = Object.keys(votesParIntervalle)[index];
                    const votes = votesParIntervalle[interval] || 0;

                    const bar = barWrapper.querySelector('.note-bar');
                    const count = barWrapper.querySelector('.note-count');
                    if (!bar || !count) return;

                    const barHeight = votes > 0 ? (90 * votes) / maxVotes : 5;
                    bar.style.height = `${barHeight}px`;

                    const color = `rgb(${255 - (votes * 255) / maxVotes}, ${(votes * 255) / maxVotes}, 50)`;
                    bar.style.backgroundColor = color;

                    count.textContent = votes;
                });
            })
            .catch((error) => {
                console.error('❌ Erreur AJAX lors de la mise à jour du graphique :', error);
            });
    };

    const loadNoteGraph = (filmId) => {
        fetch(`/films/${filmId}/notes`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) return;

                const votesParIntervalle = data.votes_par_intervalle;
                const maxVotes = Math.max(...Object.values(votesParIntervalle), 1);

                const noteBarChart = document.getElementById('note-bar-chart');
                if (!noteBarChart) return;

                noteBarChart.innerHTML = '';

                Object.keys(votesParIntervalle).forEach((interval) => {
                    const votes = votesParIntervalle[interval] || 0;

                    const barWrapper = document.createElement('div');
                    barWrapper.className = 'note-bar-wrapper';

                    const count = document.createElement('span');
                    count.className = 'note-count';
                    count.textContent = votes;

                    const bar = document.createElement('div');
                    bar.className = 'note-bar';
                    bar.style.height = `${votes > 0 ? (90 * votes) / maxVotes : 5}px`;
                    bar.style.backgroundColor = `rgb(${255 - (votes * 255) / maxVotes}, ${(votes * 255) / maxVotes}, 50)`;

                    const label = document.createElement('span');
                    label.className = 'note-label';
                    label.textContent = interval;

                    barWrapper.appendChild(count);
                    barWrapper.appendChild(bar);
                    barWrapper.appendChild(label);

                    noteBarChart.appendChild(barWrapper);
                });
            })
            .catch((error) => {
                console.error('❌ Erreur AJAX lors du chargement des notes :', error);
            });
    };

    const openModalForFilmId = async (filmId) => {
        if (!filmId) return;

        try {
            const response = await fetch(`/films/${filmId}/modal`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                closeModal();
                return;
            }

            const html = await response.text();
            modalContent.innerHTML = html;
            modalContent.setAttribute('data-id', filmId);
            modal.style.display = 'flex';

            setTimeout(() => {
                const closeButton = modalContent.querySelector('.modal-close');
                if (closeButton) {
                    closeButton.addEventListener('click', closeModal);
                }

                setupFavoriteToggle();
                setupNoteEditing();
                loadNoteGraph(filmId);
            }, 50);
        } catch (error) {
            console.error('❌ Erreur lors de la récupération des détails du film :', error);
            alert('Impossible de récupérer les détails du film.');
        }
    };

    window.openFilmModalForFilmId = openModalForFilmId;

    modal.addEventListener('click', function (event) {
        if (!modalContent.contains(event.target)) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    document.addEventListener('click', (event) => {
        const wolf = event.target.closest('.wolf-view');
        if (wolf) {
            if (modal.style.display === 'flex' && modalContent.contains(wolf)) return;

            const path = window.location.pathname || '';
            const isListePage = path.includes('/liste') && !path.includes('/ma-liste');
            if (isListePage) {
                const filmBox = wolf.closest('.film-box');
                const filmId = filmBox?.getAttribute('data-id') || wolf.getAttribute('data-id');
                if (filmId) {
                    openModalForFilmId(filmId);
                }
            }
            return;
        }

        const clickable = event.target.closest('.recent-film-item, .film-box');
        if (!clickable) return;
        if (!document.body.contains(clickable)) return;
        if (modal.style.display === 'flex' && modalContent.contains(event.target)) return;

        const filmId = clickable.getAttribute('data-id');
        openModalForFilmId(filmId);
    });
});
