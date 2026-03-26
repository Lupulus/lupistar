@extends('layouts.site')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-erreur.css') }}">
@endsection
@section('content')
    <div class="error-container">
        <img src="{{ asset('gif/logogif.GIF') }}" alt="Lupistar" class="error-logo">
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Erreur interne</h2>
        <p class="error-message">Un problème est survenu de notre côté. Réessayez plus tard ou retournez à l’accueil.</p>
        <a class="btn-home" href="{{ route('accueil') }}">Retour à l’accueil</a>
    </div>
    <div class="error-footer">
        © Lupistar — Nous travaillons à résoudre ce problème
    </div>
@endsection
