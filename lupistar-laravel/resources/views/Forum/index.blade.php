@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-forum.css') }}">
@endsection

@section('content')
    <div class="forum-page">
        <div class="forum-header">
            <div class="forum-title">
                <h1>Forum</h1>
                <p>Discussions, critiques, recommandations et support.</p>
            </div>
        </div>

        <div class="forum-categories single-column">
            @forelse($categories as $cat)
                <a class="forum-category-card" href="{{ route('forum.category', ['id' => $cat->route_id ?? $cat->id ?? '' ]) }}">
                    <div class="forum-category-left">
                        <div class="forum-category-icon" style="background: {{ $cat->couleur ?? 'var(--accent-orange)' }}"></div>
                        <div>
                            <div class="forum-category-name">{{ $cat->nom }}</div>
                            <div class="forum-category-desc">{{ $cat->description }}</div>
                        </div>
                    </div>
                    <div class="forum-category-right">
                        <div class="forum-category-stat">
                            <div class="label">Topics</div>
                            <div class="value">{{ (int) ($cat->topics_count ?? 0) }}</div>
                        </div>
                        <div class="forum-category-stat">
                            <div class="label">Vues</div>
                            <div class="value">{{ (int) ($cat->views ?? 0) }}</div>
                        </div>
                        <div class="forum-category-stat">
                            <div class="label">Activité</div>
                            <div class="value">{{ $cat->last_activity_at ? \Carbon\Carbon::parse($cat->last_activity_at)->locale('fr')->diffForHumans() : '—' }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="forum-empty">Aucune catégorie</div>
            @endforelse
        </div>
    </div>
@endsection
