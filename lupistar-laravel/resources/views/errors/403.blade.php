@extends('layouts.site')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-erreur.css') }}">
@endsection
@section('content')
    <div class="error-container">
        <img src="{{ asset('gif/logogif.GIF') }}" alt="Lupistar" class="error-logo">
        <h1 class="error-code">403</h1>
        <h2 class="error-title">Accès refusé</h2>
        <p class="error-message">Vous n’avez pas les droits nécessaires pour accéder à cette page.</p>
        <a class="btn-home" href="{{ route('accueil') }}">Retour à l’accueil</a>
    </div>
    <div class="error-footer">
        © Lupistar — Contactez un administrateur si vous pensez qu’il s’agit d’une erreur
    </div>
@endsection
