@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-account.css') }}">
@endsection

@section('content')
    <main class="account-container">
        <div class="account-info">
            <h2>Informations du compte</h2>
            <div class="profile-photo-section">
                <img src="{{ asset($photo_profil) }}" alt="Photo de profil" class="profile-photo">
                <form method="post" enctype="multipart/form-data" style="display:inline;">
                    @csrf
                    <input type="file" name="profile_photo" accept="image/*" style="display:none;" id="photo-input">
                    <button type="button" class="photo-upload-btn">Changer la photo</button>
                </form>
            </div>
            <div class="info-group">
                <label>Pseudo</label>
                <input type="text" value="{{ $username }}" disabled>
            </div>
            <div class="info-group">
                <label>Titre</label>
                <input type="text" value="{{ $titre }}" disabled>
            </div>
            @if(session('status'))
                <div class="message success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('mon-compte.update-email') }}">
                @csrf
                <div class="info-group">
                    <label for="new_email">Adresse e-mail</label>
                    <input type="email" id="new_email" name="new_email" value="{{ $email }}" required>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">Mettre à jour l'e-mail</button>
                </div>
            </form>
            <form method="post" action="{{ route('mon-compte.update-password') }}" style="margin-top: 30px;">
                @csrf
                <h3 style="color: var(--accent-orange); margin-bottom: 20px;">Changer le mot de passe</h3>
                <div class="info-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div class="info-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <div class="info-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">Changer le mot de passe</button>
                </div>
            </form>
        </div>

        <div class="middle-column">
            <div class="notifications-section">
                <h2>Notifications</h2>
                <div class="notification-container" id="notification-container">
                    <p class="no-notifications" id="no-notifications">Chargement des notifications...</p>
                </div>
            </div>

            <div class="preferences-section">
                <h2>Préférences</h2>
                <div class="preference-group">
                    <h3>Ordre d'affichage des catégories</h3>
                    <p class="preference-description">Glissez-déposez les catégories pour personnaliser leur ordre d'affichage sur les pages d'accueil et de liste.</p>
                    <div class="categories-reorder-container">
                        <ul id="categories-sortable" class="categories-list"></ul>
                        <div class="preference-actions">
                            <button type="button" id="save-categories-order" class="btn-primary">Sauvegarder l'ordre</button>
                            <button type="button" id="reset-categories-order" class="btn-secondary">Réinitialiser</button>
                        </div>
                        <div id="categories-notification" class="categories-notification" style="display: none;">
                            <span id="categories-notification-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-section">
            <h2>Mes statistiques</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number">{{ $total_films }}</span>
                    <span class="stat-label">Films vus</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ $avg_rating !== null ? number_format($avg_rating, 1) : '0.0' }}/10</span>
                    <span class="stat-label">Note moyenne</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ $rated_films }}</span>
                    <span class="stat-label">Films notés</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ $approved_films }}</span>
                    <span class="stat-label">Films proposés approuvés</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ $recompenses }}</span>
                    <span class="stat-label">Récompenses</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ $avertissements }}</span>
                    <span class="stat-label">Avertissements</span>
                </div>
            </div>

            <div class="category-stats">
                <h3>Films par catégorie</h3>
                @if(!empty($stats_categories))
                    @foreach($stats_categories as $category => $count)
                        <div class="category-item">
                            <span class="category-name">{{ $category }}</span>
                            <span class="category-count">{{ $count }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="category-item">
                        <span class="category-name">Aucun film dans votre liste</span>
                        <span class="category-count">0</span>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('scripts-js/image-crop.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            loadCategoriesPreferences();
        });

        async function loadNotifications() {
            const container = document.getElementById('notification-container');
            const empty = document.getElementById('no-notifications');
            try {
                const res = await fetch('{{ route('notifications.list') }}');
                const data = await res.json();
                container.innerHTML = '';
                if (!data.success || !Array.isArray(data.notifications) || data.notifications.length === 0) {
                    empty && (empty.style.display = 'block');
                    if (!empty) {
                        const p = document.createElement('p');
                        p.className = 'no-notifications';
                        p.textContent = 'Aucune notification';
                        container.appendChild(p);
                    }
                    return;
                }
                data.notifications.forEach(n => {
                    const div = document.createElement('div');
                    div.className = 'notification-item ' + (n.lu ? 'read' : 'unread');
                    div.innerHTML = `
                        <div class="notification-header">
                            <h3 class="notification-title">${escapeHtml(n.titre || 'Notification')}</h3>
                            <div class="notification-date">${new Date(n.date_creation).toLocaleString()}</div>
                        </div>
                        <div class="notification-message">${renderNotificationMessage(n.message || '')}</div>
                        <div class="notification-actions">
                            <button class="btn-secondary" data-action="read" data-id="${n.id}">Marquer comme lue</button>
                            <button class="btn-delete-notification" data-action="delete" data-id="${n.id}">Supprimer</button>
                        </div>
                    `;
                    container.appendChild(div);
                });
                container.addEventListener('click', async (e) => {
                    const btn = e.target.closest('button');
                    if (!btn) return;
                    const id = btn.getAttribute('data-id');
                    const action = btn.getAttribute('data-action');
                    if (action === 'read') {
                        await fetch(`/notifications/${id}/read`, { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content }});
                        loadNotifications();
                        if (window.notificationBadgeManager) window.notificationBadgeManager.forceUpdate();
                    }
                    if (action === 'delete') {
                        await fetch(`/notifications/${id}`, { method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content }});
                        loadNotifications();
                        if (window.notificationBadgeManager) window.notificationBadgeManager.forceUpdate();
                    }
                }, { once: true });
            } catch (e) {
                container.innerHTML = '<p class="no-notifications">Erreur de chargement</p>';
            }
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        function renderNotificationMessage(message) {
            const escaped = escapeHtml(message);
            return escaped.replace(/(https?:\/\/[^\s<]+)/g, (url) => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
        }

        async function loadCategoriesPreferences() {
            const ul = document.getElementById('categories-sortable');
            try {
                const res = await fetch('{{ route('preferences.categories-order.get') }}');
                const data = await res.json();
                const order = Array.isArray(data.order) ? data.order : ['Film','Animation','Anime','Série',"Série d\\'Animation"];
                ul.innerHTML = '';
                order.forEach(cat => {
                    const li = document.createElement('li');
                    li.className = 'category-item';
                    li.setAttribute('draggable', 'true');
                    li.innerHTML = `<span class="drag-handle">☰</span><span class="category-name">${cat}</span>`;
                    ul.appendChild(li);
                });
                enableDragSort(ul);
            } catch (e) {
                // silence
            }

            document.getElementById('save-categories-order').addEventListener('click', async () => {
                const order = Array.from(document.querySelectorAll('#categories-sortable .category-name')).map(el => el.textContent);
                const res = await fetch('{{ route('preferences.categories-order.save') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ order })
                });
                const ok = (await res.json()).success;
                showPrefNotice(ok ? 'Ordre sauvegardé' : 'Erreur lors de la sauvegarde', ok ? 'success' : 'error');
            });
            document.getElementById('reset-categories-order').addEventListener('click', () => {
                loadCategoriesPreferences();
                showPrefNotice('Réinitialisé', 'success');
            });
        }

        function showPrefNotice(text, cls) {
            const box = document.getElementById('categories-notification');
            const span = document.getElementById('categories-notification-text');
            span.textContent = text;
            box.className = `categories-notification show ${cls}`;
            box.style.display = 'block';
            setTimeout(() => { box.style.display = 'none'; }, 2500);
        }

        function enableDragSort(list) {
            let dragEl = null;
            list.addEventListener('dragstart', (e) => {
                dragEl = e.target.closest('.category-item');
                if (dragEl) dragEl.classList.add('dragging');
            });
            list.addEventListener('dragend', (e) => {
                const el = e.target.closest('.category-item');
                if (el) el.classList.remove('dragging');
                dragEl = null;
            });
            list.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterEl = getDragAfterElement(list, e.clientY);
                const current = document.querySelector('.category-item.dragging');
                if (!current) return;
                if (afterEl == null) {
                    list.appendChild(current);
                } else {
                    list.insertBefore(current, afterEl);
                }
            });
        }
        function getDragAfterElement(container, y) {
            const els = [...container.querySelectorAll('.category-item:not(.dragging)')];
            return els.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height/2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
    </script>
@endsection
