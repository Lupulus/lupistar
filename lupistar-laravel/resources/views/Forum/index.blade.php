@extends('layouts.site')

@section('content')
    <div style="padding:20px;">
        <h1>Forum</h1>
        <ul>
            @forelse($categories as $cat)
                <li>
                    <a href="{{ route('forum.category', ['id' => $cat->id]) }}">{{ $cat->nom }}</a>
                </li>
            @empty
                <li>Aucune catégorie</li>
            @endforelse
        </ul>
    </div>
@endsection
