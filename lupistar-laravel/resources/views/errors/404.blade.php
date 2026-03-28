@extends('layouts.site')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-erreur.css') }}">
@endsection
@section('content')
    <div class="error-container">
        <img src="{{ asset('gif/logogif.GIF') }}" alt="Lupistar" class="error-logo">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Page introuvable</h2>
        <p class="error-message">La ressource demandée n’existe pas ou a été déplacée.</p>
        <a class="btn-home" href="{{ route('accueil') }}">Retour à l’accueil</a>
    </div>
    <div class="error-footer">
        © Lupistar — Retournez à l’accueil ou utilisez le menu pour continuer
    </div>
@endsection
