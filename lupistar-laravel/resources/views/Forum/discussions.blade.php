@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-forum.css') }}">
@endsection

@section('content')
    @php
        $isLoggedIn = (bool) session('user_id');
    @endphp

    <div class="forum-page">
        <div class="forum-breadcrumbs">
            <a href="{{ route('forum') }}">Forum</a>
            <span>/</span>
            <span>{{ $category->nom }}</span>
        </div>

        <div class="forum-header">
            <div class="forum-title">
                <h1>{{ $category->nom }}</h1>
                <p>{{ $category->description }}</p>
            </div>

            <form class="forum-toolbar" method="get" action="{{ route('forum.category', ['id' => $category->route_id ?? $category->id ?? '' ]) }}">
                <input class="forum-input" id="topicSearch" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Rechercher un topic, un mot-clé, un @membre, un film…">
                <select class="forum-select" id="topicSort" name="sort">
                    <option value="activity" {{ ($sort ?? 'activity') === 'activity' ? 'selected' : '' }}>Activité</option>
                    <option value="recent" {{ ($sort ?? '') === 'recent' ? 'selected' : '' }}>Plus récents</option>
                    <option value="popular" {{ ($sort ?? '') === 'popular' ? 'selected' : '' }}>Popularité</option>
                </select>
                @if(!empty($film_id))
                    <input type="hidden" name="film_id" value="{{ (int) $film_id }}">
                @endif
                <button class="forum-button secondary" type="submit">Filtrer</button>
                <a class="forum-button" href="#new-topic">Nouveau topic</a>
            </form>
        </div>

        <div class="forum-topics" id="topicList">
            @forelse($discussions as $d)
                <a class="forum-topic" href="{{ route('forum.discussion', ['id' => $d->route_id ?? $d->id ?? '' ]) }}">
                    <div>
                        <h3 class="forum-topic-title">
                            {{ $d->pinned ? '📌 ' : '' }}{{ $d->locked ? '🔒 ' : '' }}{{ $d->titre }}
                        </h3>
                        <div class="forum-topic-meta">
                            <span class="forum-pill">👤 {{ $d->author?->username ?? '—' }}</span>
                            <span class="forum-pill">🕒 {{ \Carbon\Carbon::parse($d->updated_at ?? $d->created_at)->locale('fr')->diffForHumans() }}</span>
                            <span class="forum-pill">👁️ {{ (int) ($d->views ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="forum-topic-right">
                        <div class="forum-count">
                            <div class="n">{{ (int) ($d->replies_count ?? 0) }}</div>
                            <div class="t">Réponses</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="forum-empty">Aucun topic trouvé.</div>
            @endforelse
        </div>

        <div id="new-topic" class="forum-card">
            <h2 style="margin: 0 0 8px; color: var(--text-white);">Créer un topic</h2>
            @if(! $isLoggedIn)
                <div class="forum-empty">Connecte-toi pour publier un topic.</div>
            @else
                <form action="{{ route('forum.discussion.store') }}" method="post" id="topicForm">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $category->route_id ?? $category->id ?? '' }}">
                    <input class="forum-editor-title" id="titre" name="titre" type="text" required maxlength="120" value="{{ old('titre') }}" placeholder="Titre du topic">
                    @error('titre')<div class="forum-empty">{{ $message }}</div>@enderror

                    <div class="forum-editor-toolbar">
                        <button class="forum-editor-btn" type="button" data-cmd="bold">Gras</button>
                        <button class="forum-editor-btn" type="button" data-cmd="underline">Souligné</button>
                        <button class="forum-editor-btn" type="button" data-cmd="strike">Barré</button>
                        <button class="forum-editor-btn" type="button" data-cmd="color" data-color="#ff8c00">Orange</button>
                        <button class="forum-editor-btn" type="button" data-cmd="color" data-color="#3498db">Bleu</button>
                        <button class="forum-editor-btn" type="button" data-cmd="color" data-color="#2ecc71">Vert</button>
                        <button class="forum-editor-btn" type="button" data-cmd="emoji" data-emoji="😀">😀</button>
                        <button class="forum-editor-btn" type="button" data-cmd="emoji" data-emoji="🔥">🔥</button>
                        <button class="forum-editor-btn" type="button" data-cmd="emoji" data-emoji="🎬">🎬</button>
                        <button class="forum-editor-btn" type="button" data-cmd="img">Image</button>
                        <button class="forum-editor-btn" type="button" data-cmd="film">Ajouter un film</button>
                    </div>

                    <textarea class="forum-editor-textarea" id="description" name="description" required rows="8" placeholder="Écris ton message… (tu peux citer un membre avec @NomDuMembre)">{{ old('description') }}</textarea>
                    @error('description')<div class="forum-empty">{{ $message }}</div>@enderror

                    <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                        <button class="forum-button" type="submit">Publier</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="forum-modal-overlay" id="forumFilmModal">
        <div class="forum-modal" role="dialog" aria-modal="true">
            <div class="forum-modal-head">
                <h3 class="forum-modal-title">Ajouter un film</h3>
                <button class="forum-modal-close" type="button" id="forumFilmModalClose">Fermer</button>
            </div>
            <div style="margin-top:10px;">
                <input class="forum-input" type="text" id="forumFilmSearch" placeholder="Rechercher un film… (min 2 caractères)">
            </div>
            <div class="forum-modal-list" id="forumFilmResults"></div>
        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const filmSearchUrl = @json(route('forum.api.films'));
        const topicsSearchUrl = @json(route('forum.api.topics'));
        const topicsCategoryId = @json((string) ($category->route_id ?? $category->id ?? ''));
        const topicsFilmId = @json(!empty($film_id) ? (int) $film_id : null);

        function insertAtSelection(textarea, before, after, fallbackText) {
            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || 0;
            const selected = textarea.value.slice(start, end) || fallbackText || '';
            const next = textarea.value.slice(0, start) + before + selected + after + textarea.value.slice(end);
            textarea.value = next;
            const cursor = start + before.length + selected.length + after.length;
            textarea.focus();
            textarea.setSelectionRange(cursor, cursor);
        }

        function insertRaw(textarea, raw) {
            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || 0;
            textarea.value = textarea.value.slice(0, start) + raw + textarea.value.slice(end);
            const cursor = start + raw.length;
            textarea.focus();
            textarea.setSelectionRange(cursor, cursor);
        }

        const textarea = document.getElementById('description');
        document.querySelectorAll('.forum-editor-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!textarea) return;
                const cmd = btn.getAttribute('data-cmd');
                if (cmd === 'bold') insertAtSelection(textarea, '**', '**', 'texte');
                if (cmd === 'underline') insertAtSelection(textarea, '__', '__', 'texte');
                if (cmd === 'strike') insertAtSelection(textarea, '~~', '~~', 'texte');
                if (cmd === 'color') {
                    const c = btn.getAttribute('data-color') || '#ff8c00';
                    insertAtSelection(textarea, `[color=${c}]`, '[/color]', 'texte');
                }
                if (cmd === 'emoji') insertRaw(textarea, btn.getAttribute('data-emoji') || '');
                if (cmd === 'img') {
                    const url = prompt('URL de l’image (https://...)');
                    if (url && url.trim()) insertRaw(textarea, `[img]${url.trim()}[/img]`);
                }
                if (cmd === 'film') openFilmModal();
            });
        });

        const modal = document.getElementById('forumFilmModal');
        const modalClose = document.getElementById('forumFilmModalClose');
        const filmInput = document.getElementById('forumFilmSearch');
        const filmResults = document.getElementById('forumFilmResults');
        let lastFilmQuery = '';

        function openFilmModal() {
            if (!modal) return;
            modal.style.display = 'flex';
            if (filmInput) {
                filmInput.value = '';
                filmResults && (filmResults.innerHTML = '');
                filmInput.focus();
            }
        }

        function closeFilmModal() {
            modal && (modal.style.display = 'none');
        }

        modalClose && modalClose.addEventListener('click', closeFilmModal);
        modal && modal.addEventListener('click', (e) => {
            if (e.target === modal) closeFilmModal();
        });

        async function fetchFilms(q) {
            const url = new URL(filmSearchUrl, window.location.origin);
            url.searchParams.set('q', q);
            const r = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) return [];
            const data = await r.json();
            return Array.isArray(data.items) ? data.items : [];
        }

        function renderFilms(items) {
            if (!filmResults) return;
            if (!items.length) {
                filmResults.innerHTML = '<div class="forum-modal-item" style="opacity:.7;cursor:default;">Aucun résultat</div>';
                return;
            }
            filmResults.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'forum-modal-item';
                const year = item.date_sortie ? ` (${item.date_sortie})` : '';
                div.textContent = `${item.nom_film}${year}`;
                div.addEventListener('click', () => {
                    const label = `${item.nom_film}${year}`;
                    const token = `[film:${item.id}:${label}]`;
                    insertRaw(textarea, token);
                    closeFilmModal();
                });
                filmResults.appendChild(div);
            });
        }

        let filmTimer = null;
        filmInput && filmInput.addEventListener('input', () => {
            const q = (filmInput.value || '').trim();
            if (q.length < 2) {
                filmResults && (filmResults.innerHTML = '');
                return;
            }
            if (q === lastFilmQuery) return;
            lastFilmQuery = q;
            filmTimer && clearTimeout(filmTimer);
            filmTimer = setTimeout(async () => {
                const items = await fetchFilms(q);
                renderFilms(items);
            }, 200);
        });

        const topicList = document.getElementById('topicList');
        const topicSearch = document.getElementById('topicSearch');
        const topicSort = document.getElementById('topicSort');

        function timeAgoFr(dateStr) {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '';
            const diff = date.getTime() - Date.now();
            const sec = Math.round(diff / 1000);
            const abs = Math.abs(sec);
            const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
            if (abs < 60) return rtf.format(sec, 'second');
            const min = Math.round(sec / 60);
            if (Math.abs(min) < 60) return rtf.format(min, 'minute');
            const hour = Math.round(min / 60);
            if (Math.abs(hour) < 24) return rtf.format(hour, 'hour');
            const day = Math.round(hour / 24);
            if (Math.abs(day) < 30) return rtf.format(day, 'day');
            const month = Math.round(day / 30);
            if (Math.abs(month) < 12) return rtf.format(month, 'month');
            const year = Math.round(month / 12);
            return rtf.format(year, 'year');
        }

        function renderTopics(items) {
            if (!topicList) return;
            if (!items.length) {
                topicList.innerHTML = '<div class="forum-empty">Aucun topic trouvé.</div>';
                return;
            }
            topicList.innerHTML = '';
            items.forEach(d => {
                const a = document.createElement('a');
                a.className = 'forum-topic';
                a.href = @json(url('/forum/discussion')).replace(/\/$/, '') + '/' + encodeURIComponent(d.id);
                const pin = d.pinned ? '📌 ' : '';
                const lock = d.locked ? '🔒 ' : '';
                const when = timeAgoFr(d.updated_at || d.created_at);
                a.innerHTML = `
                    <div>
                        <h3 class="forum-topic-title">${pin}${lock}${escapeHtml(d.titre || '')}</h3>
                        <div class="forum-topic-meta">
                            <span class="forum-pill">👤 ${escapeHtml(d.author || '—')}</span>
                            <span class="forum-pill">🕒 ${escapeHtml(when || '')}</span>
                            <span class="forum-pill">👁️ ${Number(d.views || 0)}</span>
                        </div>
                    </div>
                    <div class="forum-topic-right">
                        <div class="forum-count">
                            <div class="n">${Number(d.replies_count || 0)}</div>
                            <div class="t">Réponses</div>
                        </div>
                    </div>
                `;
                topicList.appendChild(a);
            });
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        let topicTimer = null;
        async function refreshTopics() {
            if (!topicList) return;
            const url = new URL(topicsSearchUrl, window.location.origin);
            url.searchParams.set('category_id', String(topicsCategoryId));
            url.searchParams.set('q', (topicSearch?.value || '').trim());
            url.searchParams.set('sort', topicSort?.value || 'activity');
            if (topicsFilmId) url.searchParams.set('film_id', String(topicsFilmId));
            const r = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) return;
            const data = await r.json().catch(() => null);
            if (!data || !data.success || !Array.isArray(data.items)) return;
            renderTopics(data.items);
        }

        topicSearch && topicSearch.addEventListener('input', () => {
            topicTimer && clearTimeout(topicTimer);
            topicTimer = setTimeout(refreshTopics, 200);
        });
        topicSort && topicSort.addEventListener('change', refreshTopics);
    </script>
@endsection
