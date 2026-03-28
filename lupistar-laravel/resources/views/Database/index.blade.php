@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-database.css') }}">
@endsection

@section('content')
    <div class="database-container">
        <h2>Base de données</h2>

        @if(!($isLocal ?? false))
            <div class="database-warning">
                Vous devez être connecté en local
            </div>
        @else
            <div class="database-actions">
                <a class="database-btn" href="/bddadmin" target="_blank" rel="noopener noreferrer">PhpMyAdmin</a>
                <button class="database-btn disabled" type="button" disabled>Mongo</button>
            </div>
        @endif
    </div>
@endsection
