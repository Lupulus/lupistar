@extends('layouts.site')

@section('content')
    <div style="padding:20px;max-width:920px;margin:0 auto;">
        <h2>{{ $discussion->titre }}</h2>
        <p>{{ $discussion->description }}</p>
        <hr>
        <h3>Commentaires</h3>
        <ul>
            @forelse($comments as $c)
                <li>
                    <div>{{ $c->content }}</div>
                </li>
            @empty
                <li>Aucun commentaire</li>
            @endforelse
        </ul>
        <hr>
        <form action="{{ route('forum.comment.store') }}" method="post">
            @csrf
            <input type="hidden" name="discussion_id" value="{{ $discussion->id }}">
            <div>
                <label for="content">Votre message</label>
                <textarea id="content" name="content" rows="5" required>{{ old('content') }}</textarea>
                @error('content')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <button type="submit">Publier</button>
            </div>
        </form>
    </div>
@endsection
