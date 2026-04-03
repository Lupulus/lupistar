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
                <div style="margin-top:8px;font-size:12px;opacity:.85;">
                    IP détectée : {{ $ip ?? 'n/a' }}
                </div>
            </div>
        @else
            <div class="database-actions">
                <a class="database-btn" href="/bddadmin" target="_blank" rel="noopener noreferrer">Base relationnelle</a>
                <a class="database-btn" href="https://cloud.mongodb.com/v2/69c91a62d4b477bd75d03b57#/clusters" target="_blank" rel="noopener noreferrer">Base NoSQL</a>
            </div>
        @endif
    </div>
@endsection
