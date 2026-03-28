@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-admin.css') }}">
@endsection

@section('content')
    @php
        $isSuperAdmin = session('titre') === 'Super-Admin';
        $isAdmin = session('titre') === 'Admin';
        $titresOrdre = ['Membre', 'Amateur', 'Fan', 'NoLife'];
    @endphp

    <div class="membre-container">
        <h2>Liste des Membres</h2>

        <div class="search-container-membres">
            <input type="text" id="searchMembres" placeholder="Rechercher par pseudo, email, titre ou restriction..." onkeyup="filterMembres()">
        </div>

        @if($membres->count() > 0)
            <table class="membres-table" id="membresTable">
                <thead>
                <tr>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Titre actuel</th>
                    <th>Restriction</th>
                    <th>Avertissements <span class="sort-icon" onclick="sortTable(4)" title="Trier par avertissements">⇅</span></th>
                    <th>Récompenses <span class="sort-icon" onclick="sortTable(5)" title="Trier par récompenses">⇅</span></th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($membres as $m)
                    @php
                        $canModify = $isSuperAdmin || ($isAdmin && !in_array($m->titre, ['Super-Admin', 'Admin'], true));
                        $titreSuivant = '';
                        $idx = array_search($m->titre, $titresOrdre, true);
                        if ($idx !== false && $idx < count($titresOrdre) - 1) {
                            $titreSuivant = $titresOrdre[$idx + 1];
                        }
                    @endphp
                    <tr data-id="{{ $m->id }}">
                        <td class="membre-username">
                            <img src="{{ asset($m->photo_profil ?: 'img/img-profile/profil.png') }}" alt="Photo de profil" class="membre-photo-profil">
                            <span class="membre-nom" id="username-{{ $m->id }}">{{ $m->username }}</span>
                            @if((int) ($m->demande_promotion ?? 0) === 1)
                                <span class="promotion-badge" title="Demande de promotion en cours">🔔</span>
                            @endif
                        </td>
                        <td class="membre-email" id="email-{{ $m->id }}">{{ $m->email ?? 'Non renseigné' }}</td>
                        <td class="titre-cell">
                            @if((int) ($m->demande_promotion ?? 0) === 1 && $canModify)
                                <div class="promotion-container">
                                    <div class="promotion-titles">
                                        <div class="current-title">
                                            <span class="membre-titre titre-{{ strtolower(str_replace('-', '-', $m->titre)) }}" id="titre-{{ $m->id }}">{{ $m->titre }}</span>
                                        </div>

                                        <div class="promotion-arrow-container">
                                            <button class="btn-reject promotion-btn-left" onclick="traiterPromotion({{ $m->id }}, 'reject')" title="Rejeter la promotion">✗</button>
                                            <div class="promotion-arrow">↓</div>
                                            <button class="btn-approve promotion-btn-right" onclick="traiterPromotion({{ $m->id }}, 'approve')" title="Approuver la promotion">✓</button>
                                        </div>

                                        <div class="next-title">
                                            @if($titreSuivant)
                                                <span class="membre-titre titre-{{ strtolower(str_replace('-', '-', $titreSuivant)) }}">{{ $titreSuivant }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="membre-titre titre-{{ strtolower(str_replace('-', '-', $m->titre)) }}" id="titre-{{ $m->id }}">{{ $m->titre }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="membre-restriction restriction-{{ strtolower(str_replace(' ', '-', $m->restriction ?? 'aucune')) }}" id="restriction-{{ $m->id }}">{{ $m->restriction ?? 'Aucune' }}</span>
                        </td>
                        <td class="membre-avertissements">
                            <div class="warning-reward-inline">
                                @if($canModify)
                                    <button class="control-btn minus-btn" onclick="updateWarningReward({{ $m->id }}, 'avertissements', -1)">-</button>
                                @else
                                    <button class="control-btn minus-btn disabled" disabled title="Permissions insuffisantes">-</button>
                                @endif
                                <span id="avertissements-{{ $m->id }}">{{ (int) ($m->avertissements ?? 0) }}</span>
                                @if($canModify)
                                    <button class="control-btn plus-btn" onclick="updateWarningReward({{ $m->id }}, 'avertissements', 1)">+</button>
                                @else
                                    <button class="control-btn plus-btn disabled" disabled title="Permissions insuffisantes">+</button>
                                @endif
                            </div>
                        </td>
                        <td class="membre-recompenses">
                            <div class="warning-reward-inline">
                                @if($canModify)
                                    <button class="control-btn minus-btn" onclick="updateWarningReward({{ $m->id }}, 'recompenses', -1)">-</button>
                                @else
                                    <button class="control-btn minus-btn disabled" disabled title="Permissions insuffisantes">-</button>
                                @endif
                                <span id="recompenses-{{ $m->id }}">{{ (int) ($m->recompenses ?? 0) }}</span>
                                @if($canModify)
                                    <button class="control-btn plus-btn" onclick="updateWarningReward({{ $m->id }}, 'recompenses', 1)">+</button>
                                @else
                                    <button class="control-btn plus-btn disabled" disabled title="Permissions insuffisantes">+</button>
                                @endif
                            </div>
                        </td>
                        <td class="actions-cell">
                            <div class="dropdown action-dropdown">
                                @if($canModify && (! $isSuperAdmin || ($isSuperAdmin && $m->titre !== 'Super-Admin')))
                                    <button class="dropbtn" onclick="toggleDropdown('dropdown-titre-{{ $m->id }}')">Modifier Titre</button>
                                    <div class="dropdown-content" id="dropdown-titre-{{ $m->id }}">
                                        <a href="#" onclick="confirmUpdateTitle({{ $m->id }}, 'Membre')">Membre</a>
                                        <a href="#" onclick="confirmUpdateTitle({{ $m->id }}, 'Amateur')">Amateur</a>
                                        <a href="#" onclick="confirmUpdateTitle({{ $m->id }}, 'Fan')">Fan</a>
                                        <a href="#" onclick="confirmUpdateTitle({{ $m->id }}, 'NoLife')">NoLife</a>
                                        @if(! $isAdmin)
                                            <a href="#" onclick="confirmUpdateTitle({{ $m->id }}, 'Admin')">Admin</a>
                                        @endif
                                    </div>
                                @else
                                    <button class="dropbtn disabled" disabled title="Permissions insuffisantes">Modifier Titre</button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if($canModify)
                                    <button class="dropbtn" onclick="toggleDropdown('dropdown-restriction-{{ $m->id }}')">Modifier Restriction</button>
                                    <div class="dropdown-content" id="dropdown-restriction-{{ $m->id }}">
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Aucune')">Aucune</a>
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Salon Général')">Salon Général</a>
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Salon Anime')">Salon Anime</a>
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Salon Films')">Salon Films</a>
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Salon Séries')">Salon Séries</a>
                                        <a href="#" onclick="confirmUpdateRestriction({{ $m->id }}, 'Modération Complète')">Modération Complète</a>
                                    </div>
                                @else
                                    <button class="dropbtn disabled" disabled title="Permissions insuffisantes">Modifier Restriction</button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if($canModify)
                                    <button class="dropbtn" onclick="showEmailForm({{ $m->id }})">Modifier Email</button>
                                @else
                                    <button class="dropbtn disabled" disabled title="Permissions insuffisantes">Modifier Email</button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if($canModify)
                                    <button class="dropbtn" onclick="showUsernameForm({{ $m->id }})">Modifier Pseudo</button>
                                @else
                                    <button class="dropbtn disabled" disabled title="Permissions insuffisantes">Modifier Pseudo</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: var(--text-medium-gray); font-size: 1.2em; margin: 2rem 0;">Aucun membre trouvé dans la base de données.</p>
        @endif
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const routes = {
            updateTitle: @json(route('membres.title')),
            updateRestriction: @json(route('membres.restriction')),
            updateEmail: @json(route('membres.email')),
            updateUsername: @json(route('membres.username')),
            updateWarningReward: @json(route('membres.warning-reward')),
            promotion: @json(route('membres.promotion')),
        };

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        async function postJson(url, payload) {
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload || {}),
                });
                let data = null;
                try {
                    data = await r.json();
                } catch (e) {
                    data = null;
                }
                if (!r.ok && (!data || typeof data.success === 'undefined')) {
                    return { success: false, message: 'Erreur de communication avec le serveur' };
                }
                return data || { success: false, message: 'Erreur de communication avec le serveur' };
            } catch (e) {
                return { success: false, message: 'Erreur de communication avec le serveur' };
            }
        }

        function toggleDropdown(id) {
            const allDropdowns = document.querySelectorAll('.dropdown-content');
            const allActionDropdowns = document.querySelectorAll('.action-dropdown');

            allDropdowns.forEach((dropdown) => {
                if (dropdown.id !== id) dropdown.classList.remove('show');
            });
            allActionDropdowns.forEach((actionDropdown) => actionDropdown.classList.remove('active'));

            const dropdown = document.getElementById(id);
            if (!dropdown) return;
            const parentActionDropdown = dropdown.closest('.action-dropdown');
            if (!parentActionDropdown) return;

            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                parentActionDropdown.classList.remove('active');
                return;
            }

            const button = parentActionDropdown.querySelector('.dropbtn');
            const rect = button.getBoundingClientRect();
            dropdown.style.left = (rect.right - 160) + 'px';
            dropdown.style.top = (rect.bottom + 5) + 'px';

            dropdown.classList.add('show');
            parentActionDropdown.classList.add('active');
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('.action-dropdown')) return;
            document.querySelectorAll('.dropdown-content.show').forEach((d) => d.classList.remove('show'));
            document.querySelectorAll('.action-dropdown.active').forEach((d) => d.classList.remove('active'));
        });

        async function confirmUpdateTitle(id, newTitle) {
            const confirmed = await customConfirm('Êtes-vous sûr de vouloir modifier le titre de ce membre?', 'Confirmation de modification');
            if (confirmed) updateTitle(id, newTitle);
        }

        async function confirmUpdateRestriction(id, newRestriction) {
            const confirmed = await customConfirm('Êtes-vous sûr de vouloir modifier la restriction de ce membre?', 'Confirmation de modification');
            if (confirmed) updateRestriction(id, newRestriction);
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function showEmailForm(id) {
            const currentEmail = document.getElementById('email-' + id)?.textContent || '';
            const newEmail = prompt('Entrez la nouvelle adresse email:', currentEmail);
            if (newEmail !== null && newEmail.trim() !== '') {
                if (validateEmail(newEmail)) {
                    updateEmail(id, newEmail.trim());
                } else {
                    customAlert('Veuillez entrer une adresse email valide.', 'Email invalide');
                }
            }
        }

        function showUsernameForm(id) {
            const currentUsername = document.getElementById('username-' + id)?.textContent || '';
            const newUsername = prompt('Entrez le nouveau pseudo:', currentUsername);
            if (newUsername !== null && newUsername.trim() !== '') {
                updateUsername(id, newUsername.trim());
            }
        }

        async function updateTitle(id, newTitle) {
            const response = await postJson(routes.updateTitle, { id: id, newTitle: newTitle });
            if (response.success) {
                const titreElement = document.getElementById('titre-' + id);
                if (titreElement) {
                    titreElement.textContent = response.newValue;
                    titreElement.className = 'membre-titre titre-' + response.newValue.toLowerCase().replace('-', '-');
                }
                showNotification(response.message || 'Titre mis à jour', 'success');
            } else {
                showNotification('Erreur: ' + (response.message || 'Erreur'), 'error');
            }
        }

        async function updateRestriction(id, newRestriction) {
            const response = await postJson(routes.updateRestriction, { id: id, newRestriction: newRestriction });
            if (response.success) {
                const restrictionElement = document.getElementById('restriction-' + id);
                if (restrictionElement) {
                    restrictionElement.textContent = response.newValue;
                    restrictionElement.className = 'membre-restriction restriction-' + response.newValue.toLowerCase().replace(/\s+/g, '-');
                }
                showNotification(response.message || 'Restriction mise à jour', 'success');
            } else {
                showNotification('Erreur: ' + (response.message || 'Erreur'), 'error');
            }
        }

        async function updateEmail(id, newEmail) {
            const response = await postJson(routes.updateEmail, { id: id, newEmail: newEmail });
            if (response.success) {
                const emailElement = document.getElementById('email-' + id);
                if (emailElement) emailElement.textContent = response.newValue;
                showNotification(response.message || 'Email mis à jour', 'success');
            } else {
                showNotification('Erreur: ' + (response.message || 'Erreur'), 'error');
            }
        }

        async function updateUsername(id, newUsername) {
            const response = await postJson(routes.updateUsername, { id: id, newUsername: newUsername });
            if (response.success) {
                const usernameSpan = document.getElementById('username-' + id);
                if (usernameSpan) usernameSpan.textContent = response.newValue;
                showNotification(response.message || 'Pseudo mis à jour', 'success');
            } else {
                showNotification('Erreur: ' + (response.message || 'Erreur'), 'error');
            }
        }

        async function updateWarningReward(id, type, increment) {
            const response = await postJson(routes.updateWarningReward, { id: id, type: type, increment: increment });
            if (response.success) {
                const element = document.getElementById(type + '-' + id);
                if (element) element.textContent = response.newValue;
                showNotification('Mise à jour réussie', 'success');
            } else {
                showNotification('Erreur: ' + (response.message || 'Erreur'), 'error');
            }
        }

        function filterMembres() {
            const input = document.getElementById('searchMembres');
            const filter = (input?.value || '').toUpperCase();
            const table = document.getElementById('membresTable');
            if (!table) return;
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                tr[i].style.display = 'none';
                const td = tr[i].getElementsByTagName('td');
                for (let j = 0; j < 4; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = '';
                            break;
                        }
                    }
                }
            }
        }

        const sortOrder = {};

        function sortTable(columnIndex) {
            const table = document.getElementById('membresTable');
            if (!table) return;

            if (!sortOrder[columnIndex]) {
                sortOrder[columnIndex] = 'asc';
            } else {
                sortOrder[columnIndex] = sortOrder[columnIndex] === 'asc' ? 'desc' : 'asc';
            }

            const dir = sortOrder[columnIndex];
            let switching = true;
            let switchcount = 0;

            while (switching) {
                switching = false;
                const rows = table.rows;
                for (let i = 1; i < (rows.length - 1); i++) {
                    let shouldSwitch = false;
                    const x = rows[i].getElementsByTagName('TD')[columnIndex];
                    const y = rows[i + 1].getElementsByTagName('TD')[columnIndex];
                    const xValue = parseInt(x.textContent || x.innerText) || 0;
                    const yValue = parseInt(y.textContent || y.innerText) || 0;
                    if (dir === 'asc') {
                        if (xValue > yValue) {
                            shouldSwitch = true;
                            break;
                        }
                    } else {
                        if (xValue < yValue) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else if (switchcount === 0 && dir === 'asc') {
                    sortOrder[columnIndex] = 'desc';
                    switching = true;
                }
            }

            updateSortIcon(columnIndex, sortOrder[columnIndex]);
        }

        function updateSortIcon(columnIndex, direction) {
            const headers = document.querySelectorAll('.sort-icon');
            headers.forEach((icon) => icon.innerHTML = '⇅');
            const currentIcon = headers[columnIndex - 4];
            if (currentIcon) currentIcon.innerHTML = direction === 'asc' ? '↑' : '↓';
        }

        function traiterPromotion(userId, action) {
            const actionText = action === 'approve' ? 'approuver' : 'rejeter';
            customConfirm(`Êtes-vous sûr de vouloir ${actionText} cette demande de promotion ?`, 'Confirmation de promotion')
                .then((confirmed) => {
                    if (!confirmed) return;
                    return postJson(routes.promotion, { user_id: userId, action: action });
                })
                .then((data) => {
                    if (!data) return;
                    if (data.success) {
                        showNotification(data.message || 'Promotion mise à jour', 'success');
                        const row = document.querySelector(`tr[data-id="${userId}"]`);
                        if (!row) return;
                        const badge = row.querySelector('.promotion-badge');
                        if (badge) badge.remove();
                        const promotionContainer = row.querySelector('.promotion-container');
                        if (!promotionContainer) return;

                        const current = document.getElementById('titre-' + userId);
                        const newTitle = action === 'approve' && data.new_title ? data.new_title : (current?.textContent || '');
                        const newTitleElement = document.createElement('span');
                        newTitleElement.className = 'membre-titre titre-' + (newTitle || '').toLowerCase().replace('-', '-');
                        newTitleElement.id = 'titre-' + userId;
                        newTitleElement.textContent = newTitle;
                        promotionContainer.parentNode.replaceChild(newTitleElement, promotionContainer);
                    } else {
                        showNotification(data.message || 'Erreur lors du traitement', 'error');
                    }
                })
                .catch(() => {
                    showNotification('Erreur lors du traitement de la demande', 'error');
                });
        }
    </script>
@endsection
