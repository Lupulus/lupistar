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
                    <th>Restrictions</th>
                    <th>Avertissements <span class="sort-icon" onclick="sortTable(4)" title="Trier par avertissements">⇅</span></th>
                    <th>Récompenses <span class="sort-icon" onclick="sortTable(5)" title="Trier par récompenses">⇅</span></th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($membres as $m)
                    @php
                        $canModify = $isSuperAdmin || ($isAdmin && !in_array($m->titre, ['Super-Admin', 'Admin'], true));
                        $rawRestrictions = (string) ($m->restriction ?? '');
                        $currentRestrictions = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), explode(',', $rawRestrictions)), static fn ($v) => $v !== '' && $v !== 'Aucune'));
                        $deletionScheduledFor = ! empty($m->deletion_scheduled_for ?? null) ? \Illuminate\Support\Carbon::parse($m->deletion_scheduled_for) : null;
                        $isDeletionPending = $deletionScheduledFor && $deletionScheduledFor->isFuture();
                        $hoursLeft = $isDeletionPending ? max(0, (int) ceil(now()->diffInMinutes($deletionScheduledFor) / 60)) : 0;
                        $titreSuivant = '';
                        $idx = array_search($m->titre, $titresOrdre, true);
                        if ($idx !== false && $idx < count($titresOrdre) - 1) {
                            $titreSuivant = $titresOrdre[$idx + 1];
                        }
                    @endphp
                    <tr data-id="{{ $m->id }}" @if($isDeletionPending) class="pending-deletion-row" @endif>
                        <td class="membre-username">
                            <div class="membre-username-inner">
                                <img src="{{ asset($m->photo_profil ?: 'img/img-profile/profil.png') }}" alt="Photo de profil" class="membre-photo-profil">
                                <span class="membre-nom" id="username-{{ $m->id }}">{{ $m->username }}</span>
                                @if((int) ($m->demande_promotion ?? 0) === 1)
                                    <span class="promotion-badge" title="Demande de promotion en cours">🔔</span>
                                @endif
                            </div>
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
                            <span class="membre-restriction" id="restriction-{{ $m->id }}">{{ $m->restriction ?? 'Aucune' }}</span>
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
                        <td class="actions-cell @if($isDeletionPending) pending-deletion @endif">
                            <div class="actions-cell-inner">
                                <div class="dropdown action-dropdown">
                                @if(! $isDeletionPending && $canModify && (! $isSuperAdmin || ($isSuperAdmin && $m->titre !== 'Super-Admin')))
                                    <button type="button" class="dropbtn action-icon-btn icon-title" onclick="toggleDropdown('dropdown-titre-{{ $m->id }}')" title="Modifier le titre" aria-label="Modifier le titre">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </button>
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
                                    <button type="button" class="dropbtn action-icon-btn icon-title disabled" disabled title="{{ $isDeletionPending ? 'Suppression en cours' : 'Permissions insuffisantes' }}" aria-label="Modifier le titre">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if(! $isDeletionPending && $canModify)
                                    <button type="button" class="dropbtn action-icon-btn icon-restrictions" onclick="toggleDropdown('dropdown-restriction-{{ $m->id }}')" title="Modifier les restrictions" aria-label="Modifier les restrictions">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5Z"></path>
                                            <path d="M9 12l2 2 4-4"></path>
                                        </svg>
                                    </button>
                                    <div class="dropdown-content" id="dropdown-restriction-{{ $m->id }}">
                                        <div style="padding: 10px 12px; min-width: 220px;">
                                            <div style="font-weight: 700; margin-bottom: 8px;">Forum</div>
                                            <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                <input type="checkbox" value="Forum Accès Off" @if(in_array('Forum Accès Off', $currentRestrictions, true)) checked @endif>
                                                Accès OFF
                                            </label>
                                            <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                <input type="checkbox" value="Forum Écriture Off" @if(in_array('Forum Écriture Off', $currentRestrictions, true)) checked @endif>
                                                Écriture OFF
                                            </label>

                                            @if($m->titre === 'Admin')
                                                <div style="font-weight: 700; margin: 10px 0 8px;">Admin</div>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Film Approuver Off" @if(in_array('Admin Film Approuver Off', $currentRestrictions, true)) checked @endif>
                                                    Film approuver OFF
                                                </label>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Film Supprimer Off" @if(in_array('Admin Film Supprimer Off', $currentRestrictions, true)) checked @endif>
                                                    Film supprimer OFF
                                                </label>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Film Modifier Off" @if(in_array('Admin Film Modifier Off', $currentRestrictions, true)) checked @endif>
                                                    Film modifier OFF
                                                </label>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Notif Off" @if(in_array('Admin Notif Off', $currentRestrictions, true)) checked @endif>
                                                    Notif OFF
                                                </label>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Conversions Off" @if(in_array('Admin Conversions Off', $currentRestrictions, true)) checked @endif>
                                                    Conversions OFF
                                                </label>
                                                <label style="display:block; margin: 6px 0; cursor:pointer;">
                                                    <input type="checkbox" value="Admin Membres Off" @if(in_array('Admin Membres Off', $currentRestrictions, true)) checked @endif>
                                                    Membres OFF
                                                </label>
                                            @endif

                                            <div style="display:flex; gap:8px; margin-top: 10px;">
                                                <button type="button" class="dropbtn" style="flex:1;" onclick="confirmUpdateRestrictions({{ $m->id }})">Enregistrer</button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <button type="button" class="dropbtn action-icon-btn icon-restrictions disabled" disabled title="{{ $isDeletionPending ? 'Suppression en cours' : 'Permissions insuffisantes' }}" aria-label="Modifier les restrictions">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5Z"></path>
                                            <path d="M9 12l2 2 4-4"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if(! $isDeletionPending && $canModify)
                                    <button type="button" class="dropbtn action-icon-btn icon-email" onclick="showEmailForm({{ $m->id }})" title="Modifier l'email" aria-label="Modifier l'email">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 4h16v16H4z"></path>
                                            <path d="m4 7 8 6 8-6"></path>
                                        </svg>
                                    </button>
                                @else
                                    <button type="button" class="dropbtn action-icon-btn icon-email disabled" disabled title="{{ $isDeletionPending ? 'Suppression en cours' : 'Permissions insuffisantes' }}" aria-label="Modifier l'email">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 4h16v16H4z"></path>
                                            <path d="m4 7 8 6 8-6"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if(! $isDeletionPending && $canModify)
                                    <button type="button" class="dropbtn action-icon-btn icon-username" onclick="showUsernameForm({{ $m->id }})" title="Modifier le pseudo" aria-label="Modifier le pseudo">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                                            <circle cx="12" cy="8" r="4"></circle>
                                        </svg>
                                    </button>
                                @else
                                    <button type="button" class="dropbtn action-icon-btn icon-username disabled" disabled title="{{ $isDeletionPending ? 'Suppression en cours' : 'Permissions insuffisantes' }}" aria-label="Modifier le pseudo">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                                            <circle cx="12" cy="8" r="4"></circle>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="dropdown action-dropdown">
                                @if(! $isDeletionPending && $canModify)
                                    <button type="button" class="dropbtn action-icon-btn icon-delete" onclick="requestAccountDeletion({{ $m->id }})" title="Supprimer le compte (après 24h)" aria-label="Supprimer le compte">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4h8v2"></path>
                                            <path d="M6 6l1 16h10l1-16"></path>
                                            <path d="M10 11v6"></path>
                                            <path d="M14 11v6"></path>
                                        </svg>
                                    </button>
                                @else
                                    <button type="button" class="dropbtn action-icon-btn icon-delete disabled" disabled title="{{ $isDeletionPending ? 'Suppression en cours' : 'Permissions insuffisantes' }}" aria-label="Supprimer le compte">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4h8v2"></path>
                                            <path d="M6 6l1 16h10l1-16"></path>
                                            <path d="M10 11v6"></path>
                                            <path d="M14 11v6"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            </div>

                            @if($isDeletionPending)
                                <div class="deletion-row-ui" title="Compte en cours de suppression">
                                    <div class="deletion-row-clock">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"></circle>
                                            <path d="M12 7v6l4 2"></path>
                                        </svg>
                                        <span>{{ $hoursLeft }}h</span>
                                    </div>
                                    @if($canModify)
                                        <button type="button" class="cancel-delete-btn" onclick="cancelAccountDeletion({{ $m->id }})" title="Annuler la suppression" aria-label="Annuler la suppression">Annuler</button>
                                    @endif
                                </div>
                            @endif
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
            requestDeletion: @json(route('membres.deletion.request')),
            cancelDeletion: @json(route('membres.deletion.cancel.admin')),
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
            dropdown.style.left = (rect.left + (rect.width / 2)) + 'px';
            dropdown.style.top = (rect.bottom + 8) + 'px';
            dropdown.style.transform = 'translateX(-50%)';
            dropdown.classList.add('show');
            parentActionDropdown.classList.add('active');

            requestAnimationFrame(() => {
                const w = dropdown.getBoundingClientRect().width || 240;
                const half = w / 2;
                const padding = 12;
                const centerX = rect.left + (rect.width / 2);
                const left = Math.min(Math.max(centerX, padding + half), window.innerWidth - padding - half);
                dropdown.style.left = left + 'px';
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.transform = 'translateX(-50%)';
            });
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

        async function confirmUpdateRestrictions(id) {
            const confirmed = await customConfirm('Êtes-vous sûr de vouloir modifier les restrictions de ce membre?', 'Confirmation de modification');
            if (confirmed) updateRestrictions(id);
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        async function showEmailForm(id) {
            const currentEmail = document.getElementById('email-' + id)?.textContent || '';
            const newEmail = await customPrompt('Entrez la nouvelle adresse email :', 'Modifier l’email', {
                defaultValue: currentEmail,
                placeholder: 'ex: membre@email.com',
                inputType: 'email',
                validator: (v) => (validateEmail(v) ? '' : 'Veuillez entrer une adresse email valide.'),
                confirmText: 'Modifier',
            });
            if (typeof newEmail !== 'string') return;
            const value = newEmail.trim();
            if (!value) return;
            updateEmail(id, value);
        }

        async function showUsernameForm(id) {
            const currentUsername = document.getElementById('username-' + id)?.textContent || '';
            const newUsername = await customPrompt('Entrez le nouveau pseudo :', 'Modifier le pseudo', {
                defaultValue: currentUsername,
                placeholder: 'Nouveau pseudo',
                inputType: 'text',
                validator: (v) => {
                    if (!v.trim()) return 'Le pseudo ne peut pas être vide.';
                    if (v.trim().length > 30) return 'Le pseudo est trop long (max 30 caractères).';
                    return '';
                },
                confirmText: 'Modifier',
            });
            if (typeof newUsername !== 'string') return;
            const value = newUsername.trim();
            if (!value) return;
            updateUsername(id, value);
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

        function collectRestrictionsFromDropdown(id) {
            const dropdown = document.getElementById('dropdown-restriction-' + id);
            if (!dropdown) return [];
            return Array.from(dropdown.querySelectorAll('input[type="checkbox"]'))
                .filter((i) => i.checked)
                .map((i) => (i.value || '').trim())
                .filter((v) => v !== '');
        }

        async function updateRestrictions(id) {
            const restrictions = collectRestrictionsFromDropdown(id);
            const response = await postJson(routes.updateRestriction, { id: id, restrictions: restrictions });
            if (response.success) {
                const restrictionElement = document.getElementById('restriction-' + id);
                if (restrictionElement) {
                    restrictionElement.textContent = response.newValue;
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

        async function requestAccountDeletion(id) {
            const confirmed = await customConfirm(
                'Confirmer la suppression de ce compte ?\n\nAprès confirmation, un email sera envoyé au membre pour annuler si besoin. Le compte sera supprimé automatiquement après 24h.',
                'Confirmation de suppression'
            );
            if (! confirmed) return;

            const response = await postJson(routes.requestDeletion, { id: id });
            if (response.success) {
                showNotification(response.message || 'Suppression planifiée', 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showNotification(response.message || 'Erreur lors de la planification de suppression', 'error');
            }
        }

        async function cancelAccountDeletion(id) {
            const confirmed = await customConfirm('Annuler la suppression de ce compte ?', 'Annuler la suppression');
            if (! confirmed) return;

            const response = await postJson(routes.cancelDeletion, { id: id });
            if (response.success) {
                showNotification(response.message || 'Suppression annulée', 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showNotification(response.message || 'Erreur lors de l’annulation', 'error');
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

        let pendingDeletionLayoutRaf = 0;

        function layoutPendingDeletionUi() {
            const rows = document.querySelectorAll('tr.pending-deletion-row');
            rows.forEach((row) => {
                const ui = row.querySelector('.deletion-row-ui');
                if (!ui) return;
                const rect = row.getBoundingClientRect();
                ui.style.position = 'fixed';
                ui.style.left = (rect.left + (rect.width / 2)) + 'px';
                ui.style.top = (rect.top + (rect.height / 2)) + 'px';
                ui.style.transform = 'translate(-50%, -50%)';
                ui.style.zIndex = '9990';
                ui.style.pointerEvents = 'auto';
            });
        }

        function schedulePendingDeletionLayout() {
            if (pendingDeletionLayoutRaf) return;
            pendingDeletionLayoutRaf = requestAnimationFrame(() => {
                pendingDeletionLayoutRaf = 0;
                layoutPendingDeletionUi();
            });
        }

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
            schedulePendingDeletionLayout();
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

        schedulePendingDeletionLayout();
        window.addEventListener('resize', schedulePendingDeletionLayout);
        window.addEventListener('scroll', schedulePendingDeletionLayout, { passive: true });
    </script>
@endsection
