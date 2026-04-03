@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-forum.css') }}">
@endsection

@section('content')
    @php
        $isLoggedIn = (bool) session('user_id');
        $currentUserId = is_numeric(session('user_id')) ? (int) session('user_id') : null;
        $currentTitre = (string) session('titre', 'Membre');
        $isAdmin = in_array($currentTitre, ['Admin', 'Super-Admin'], true);
        $restrictionList = [];
        if ($currentUserId) {
            $raw = (string) (\DB::table('membres')->where('id', (int) $currentUserId)->value('restriction') ?? '');
            $restrictionList = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), explode(',', $raw)), static fn ($v) => $v !== '' && $v !== 'Aucune'));
        }
        $canWrite = $isLoggedIn && !in_array('Forum Écriture Off', $restrictionList, true);
        $canManageDiscussion = $canWrite && $currentUserId && ($isAdmin || $currentUserId === (int) $discussion->author_id);
        $commentById = [];
        foreach ($comments as $cc) {
            $commentById[(string) ($cc->id ?? '')] = $cc;
        }
    @endphp

    <div class="forum-page">
        <div class="forum-breadcrumbs">
            <a href="{{ route('forum') }}">Forum</a>
            <span>/</span>
            <a href="{{ route('forum.category', ['id' => $discussion->category->route_id ?? $discussion->category->id ?? '' ]) }}">{{ $discussion->category->nom }}</a>
            <span>/</span>
            <span>{{ $discussion->titre }}</span>
        </div>

        <div class="forum-card forum-discussion-card">
            <div class="forum-discussion-head">
                <div class="forum-title">
                    <h1 style="margin:0;">{{ $discussion->titre }}</h1>
                    <div class="forum-topic-meta" style="margin-top:10px;">
                        <span class="forum-pill">👤 {{ $discussion->author?->username ?? '—' }}</span>
                        <span class="forum-pill">🕒 <span class="js-timeago" data-iso="{{ \Carbon\Carbon::parse($discussion->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($discussion->created_at)->locale('fr')->diffForHumans() }}</span></span>
                        <span class="forum-pill">👁️ {{ (int) ($discussion->views ?? 0) }}</span>
                    </div>
                </div>
                @if($canManageDiscussion)
                    <div class="forum-discussion-actions">
                        <button class="forum-button secondary" type="button" id="editDiscussionBtn">Modifier</button>
                        <form action="{{ route('forum.discussion.delete', ['id' => $discussion->id]) }}" method="post" style="margin:0;">
                            @csrf
                            <button class="forum-button" type="submit" onclick="return confirm('Supprimer ce topic ?')">Supprimer</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="forum-content">{!! $discussion->description_html !!}</div>
        </div>

        @if($canManageDiscussion)
            <div class="forum-card" id="editDiscussionCard" style="display:none;">
                <h2 style="margin: 0 0 8px; color: var(--text-white);">Modifier le topic</h2>
                <form action="{{ route('forum.discussion.update', ['id' => $discussion->id]) }}" method="post">
                    @csrf
                    <input class="forum-editor-title" name="titre" type="text" required maxlength="120" value="{{ old('titre', $discussion->titre) }}">
                    <div class="forum-editor-toolbar">
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="bold">Gras</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="underline">Souligné</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="strike">Barré</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="color" data-color="#ff8c00">Orange</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="color" data-color="#3498db">Bleu</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="color" data-color="#2ecc71">Vert</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="emoji" data-emoji="😀">😀</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="emoji" data-emoji="🔥">🔥</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="emoji" data-emoji="🎬">🎬</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="img">Image</button>
                        <button class="forum-editor-btn" type="button" data-target="editDiscussionDescription" data-cmd="film">Ajouter un film</button>
                    </div>
                    <textarea class="forum-editor-textarea" id="editDiscussionDescription" name="description" rows="8" required>{{ old('description', $discussion->description) }}</textarea>
                    <div style="display:flex;justify-content:flex-end;margin-top:10px;gap:10px;">
                        <button class="forum-button secondary" type="button" id="cancelEditDiscussionBtn">Annuler</button>
                        <button class="forum-button" type="submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="forum-comments">
            @forelse($comments as $c)
                @php
                    $avatar = $c->author?->photo_profil ? asset($c->author->photo_profil) : asset('img/img-profile/profil.png');
                    $canManageComment = $canWrite && $currentUserId && ($isAdmin || $currentUserId === (int) $c->author_id);
                    $parentId = (string) ($c->parent_id ?? '');
                    $parent = $parentId !== '' ? ($commentById[$parentId] ?? null) : null;
                    $parentUsername = $parent?->author?->username ?? '';
                @endphp
                <div class="forum-comment {{ $parentId !== '' ? 'is-reply' : '' }}" id="comment-{{ $c->id }}" data-comment-id="{{ $c->id }}" data-parent-id="{{ $parentId }}" data-author="{{ $c->author?->username ?? '—' }}">
                    <img class="forum-avatar" src="{{ $avatar }}" alt="Avatar">
                    <div>
                        <div class="forum-comment-head">
                            <div class="forum-comment-user">{{ $c->author?->username ?? '—' }}</div>
                            <div class="forum-comment-date"><span class="js-timeago" data-iso="{{ \Carbon\Carbon::parse($c->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($c->created_at)->locale('fr')->diffForHumans() }}</span></div>
                        </div>
                        @if($parentId !== '' && $parentUsername !== '')
                            <div class="forum-reply-to">
                                ↪ Réponse à <a href="#comment-{{ $parentId }}">@{{ $parentUsername }}</a>
                            </div>
                        @elseif($parentId === '')
                            <div class="forum-reply-to is-topic">
                                💬 Réponse au topic
                            </div>
                        @endif
                        <div class="forum-content" id="comment-view-{{ $c->id }}">{!! $c->content_html !!}</div>
                        @if($canManageComment)
                            <form action="{{ route('forum.comment.update', ['id' => $c->id]) }}" method="post" id="comment-edit-form-{{ $c->id }}" style="display:none;margin-top:10px;">
                                @csrf
                                <div class="forum-editor-toolbar">
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="bold">Gras</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="underline">Souligné</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="strike">Barré</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="color" data-color="#ff8c00">Orange</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="color" data-color="#3498db">Bleu</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="color" data-color="#2ecc71">Vert</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="emoji" data-emoji="😀">😀</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="emoji" data-emoji="🔥">🔥</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="emoji" data-emoji="🎬">🎬</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="img">Image</button>
                                    <button class="forum-editor-btn" type="button" data-target="editComment-{{ $c->id }}" data-cmd="film">Ajouter un film</button>
                                </div>
                                <textarea class="forum-editor-textarea" id="editComment-{{ $c->id }}" name="content" rows="6" required>{{ old('content', $c->content) }}</textarea>
                                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px;">
                                    <button class="forum-button secondary" type="button" data-cancel-edit="{{ $c->id }}">Annuler</button>
                                    <button class="forum-button" type="submit">Enregistrer</button>
                                </div>
                            </form>
                        @endif
                        <div class="forum-comment-actions">
                            <button class="forum-like {{ $c->is_liked ? 'active' : '' }}" type="button" data-like-id="{{ $c->id }}">
                                <span>👍</span>
                                <span class="count" id="like-count-{{ $c->id }}">{{ (int) ($c->likes_count ?? 0) }}</span>
                            </button>
                            @if($canWrite)
                                <button class="forum-editor-btn" type="button" data-reply-to="{{ $c->id }}">Répondre</button>
                            @endif
                            @if($canManageComment)
                                <button class="forum-editor-btn" type="button" data-edit-comment="{{ $c->id }}">Modifier</button>
                                <form action="{{ route('forum.comment.delete', ['id' => $c->id]) }}" method="post" style="margin:0;">
                                    @csrf
                                    <button class="forum-editor-btn" type="submit" onclick="return confirm('Supprimer ce message ?')">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="forum-empty">Aucun message pour l’instant.</div>
            @endforelse
        </div>

        <div class="forum-card" id="reply">
            <h2 style="margin: 0 0 8px; color: var(--text-white);">Répondre</h2>
            @if(! $isLoggedIn)
                <div class="forum-empty">Connecte-toi pour répondre.</div>
            @elseif(! $canWrite)
                <div class="forum-empty">Ton compte est restreint : tu ne peux plus écrire/répondre sur le forum.</div>
            @else
                <form action="{{ route('forum.comment.store') }}" method="post" id="commentForm">
                    @csrf
                    <input type="hidden" name="discussion_id" value="{{ $discussion->id }}">
                    <input type="hidden" name="parent_id" id="parent_id" value="">
                    <div class="forum-reply-context" id="replyContext" style="display:none;"></div>

                    <div class="forum-editor-toolbar">
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="bold">Gras</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="underline">Souligné</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="strike">Barré</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="color" data-color="#ff8c00">Orange</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="color" data-color="#3498db">Bleu</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="color" data-color="#2ecc71">Vert</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="emoji" data-emoji="😀">😀</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="emoji" data-emoji="🔥">🔥</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="emoji" data-emoji="🎬">🎬</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="img">Image</button>
                        <button class="forum-editor-btn" type="button" data-target="content" data-cmd="film">Ajouter un film</button>
                    </div>

                    <textarea class="forum-editor-textarea" id="content" name="content" rows="7" required placeholder="Ton message…">{{ old('content') }}</textarea>
                    @error('content')<div class="forum-empty">{{ $message }}</div>@enderror

                    <div style="display:flex;justify-content:space-between;gap:10px;margin-top:10px;align-items:center;">
                        <button class="forum-button secondary" type="button" id="cancelReply" style="display:none;">Annuler la réponse</button>
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
        const likeUrlTemplate = @json(url('/forum/comment/__ID__/like'));
        const filmSearchUrl = @json(route('forum.api.films'));

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

        let activeTextarea = document.getElementById('content');
        document.querySelectorAll('.forum-editor-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target') || 'content';
                const textarea = document.getElementById(targetId);
                if (!textarea) return;
                activeTextarea = textarea;
                const cmd = btn.getAttribute('data-cmd');
                if (!cmd) return;
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

        document.addEventListener('click', async (e) => {
            const filmLink = e.target.closest('.forum-film-link');
            if (filmLink) {
                e.preventDefault();
                const id = filmLink.getAttribute('data-film-id');
                if (window.openFilmModalForFilmId) window.openFilmModalForFilmId(id);
                return;
            }

            const likeBtn = e.target.closest('.forum-like');
            if (likeBtn) {
                const id = likeBtn.getAttribute('data-like-id');
                if (!id) return;
                const url = likeUrlTemplate.replace('__ID__', encodeURIComponent(id));
                const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
                const data = await r.json().catch(() => null);
                if (!data || !data.success) return;
                likeBtn.classList.toggle('active', !!data.liked);
                const countEl = document.getElementById('like-count-' + id);
                if (countEl) countEl.textContent = String(data.count ?? 0);
                return;
            }

            const replyBtn = e.target.closest('[data-reply-to]');
            if (replyBtn) {
                const parentId = replyBtn.getAttribute('data-reply-to');
                const parentInput = document.getElementById('parent_id');
                const cancel = document.getElementById('cancelReply');
                const replyContext = document.getElementById('replyContext');
                if (parentInput) parentInput.value = parentId || '';
                cancel && (cancel.style.display = 'inline-flex');
                if (replyContext) {
                    const parentEl = parentId ? document.getElementById('comment-' + parentId) : null;
                    const author = parentEl?.getAttribute('data-author') || '';
                    replyContext.style.display = 'block';
                    replyContext.innerHTML = author
                        ? `↪ Réponse à <a href="#comment-${escapeHtml(parentId)}">@${escapeHtml(author)}</a>`
                        : `↪ Réponse à <a href="#comment-${escapeHtml(parentId)}">ce message</a>`;
                }
                activeTextarea = document.getElementById('content');
                activeTextarea && activeTextarea.focus();
                const anchor = document.getElementById('reply');
                anchor && anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            const editBtn = e.target.closest('[data-edit-comment]');
            if (editBtn) {
                const id = editBtn.getAttribute('data-edit-comment');
                const form = document.getElementById('comment-edit-form-' + id);
                const view = document.getElementById('comment-view-' + id);
                if (form && view) {
                    const show = form.style.display === 'none' || !form.style.display;
                    form.style.display = show ? 'block' : 'none';
                    view.style.display = show ? 'none' : 'block';
                    if (show) {
                        const ta = document.getElementById('editComment-' + id);
                        activeTextarea = ta;
                        ta && ta.focus();
                    }
                }
            }

            const cancelEdit = e.target.closest('[data-cancel-edit]');
            if (cancelEdit) {
                const id = cancelEdit.getAttribute('data-cancel-edit');
                const form = document.getElementById('comment-edit-form-' + id);
                const view = document.getElementById('comment-view-' + id);
                form && (form.style.display = 'none');
                view && (view.style.display = 'block');
            }
        });

        const cancelReply = document.getElementById('cancelReply');
        cancelReply && cancelReply.addEventListener('click', () => {
            const parentInput = document.getElementById('parent_id');
            const replyContext = document.getElementById('replyContext');
            parentInput && (parentInput.value = '');
            cancelReply.style.display = 'none';
            replyContext && (replyContext.style.display = 'none');
            replyContext && (replyContext.innerHTML = '');
        });

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        function timeAgoFr(dateStr) {
            const date = new Date(String(dateStr || ''));
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

        function updateTimeAgo(root) {
            const scope = root || document;
            scope.querySelectorAll('.js-timeago[data-iso]').forEach(el => {
                const iso = el.getAttribute('data-iso') || '';
                const s = timeAgoFr(iso);
                if (s) el.textContent = s;
            });
        }

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
                    if (activeTextarea) insertRaw(activeTextarea, token);
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

        const editDiscussionBtn = document.getElementById('editDiscussionBtn');
        const editDiscussionCard = document.getElementById('editDiscussionCard');
        const cancelEditDiscussionBtn = document.getElementById('cancelEditDiscussionBtn');
        editDiscussionBtn && editDiscussionBtn.addEventListener('click', () => {
            if (!editDiscussionCard) return;
            editDiscussionCard.style.display = 'block';
            editDiscussionCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const ta = document.getElementById('editDiscussionDescription');
            activeTextarea = ta;
            ta && ta.focus();
        });
        cancelEditDiscussionBtn && cancelEditDiscussionBtn.addEventListener('click', () => {
            editDiscussionCard && (editDiscussionCard.style.display = 'none');
        });

        updateTimeAgo(document);
        setInterval(() => updateTimeAgo(document), 30000);
    </script>
@endsection
