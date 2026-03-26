<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/style-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style-con-reg.css') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon.ico') }}">
    <title>{{ $title ?? 'Lupistar' }}</title>
</head>
<body>
    <div class="background"></div>
    @yield('content')
    <x-footer />
    <script src="{{ asset('scripts-js/background.js') }}" defer></script>
</body>
</html>

