@extends('layouts.site')

@section('content')
    <div style="padding:20px;max-width:920px;margin:0 auto;">
        <h2>{{ $category->nom }}</h2>
        <ul>
            @forelse($discussions as $d)
                <li>
                    <a href="{{ route('forum.discussion', ['id' => $d->id]) }}">{{ $d->titre }}</a>
                </li>
            @empty
                <li>Aucune discussion</li>
            @endforelse
        </ul>
        <hr>
        <form action="{{ route('forum.discussion.store') }}" method="post">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <div>
                <label for="titre">Titre</label>
                <input id="titre" name="titre" type="text" required maxlength="120" value="{{ old('titre') }}">
                @error('titre')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <label for="description">Message</label>
                <textarea id="description" name="description" required rows="6">{{ old('description') }}</textarea>
                @error('description')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <button type="submit">Créer la discussion</button>
            </div>
        </form>
    </div>
@endsection
