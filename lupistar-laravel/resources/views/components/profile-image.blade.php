@props([
    'imgId' => 'profilImg',
    'imgClass' => '',
    'imgAlt' => 'Photo de Profil',
    'imgStyle' => '',
])

@php
    $photoProfil = session('photo_profil', 'img/img-profile/profil.png');
    $normalized = str_replace('\\', '/', trim((string) $photoProfil));
    while (str_starts_with($normalized, './')) {
        $normalized = substr($normalized, 2);
    }
    while (str_starts_with($normalized, '../')) {
        $normalized = substr($normalized, 3);
    }
    $normalized = ltrim($normalized, '/');

    $computedClass = trim($imgClass);
    $isCustom = true;
    if ($normalized === 'img/img-profile/profil.png' || $normalized === 'img/profil.png') {
        $computedClass = trim($computedClass . ' profil-default');
        $isCustom = false;
    } else {
        $computedClass = trim($computedClass . ' profil-custom');
    }

    $idAttr = trim((string) $imgId) !== '' ? (string) $imgId : null;
    $styleAttr = trim((string) $imgStyle) !== '' ? (string) $imgStyle : null;
@endphp

@if($isCustom)
    <a href="{{ route('mon-compte') }}" class="profile-account-link" aria-label="Mon compte">
        <img
            src="{{ asset($normalized) }}"
            @class([$computedClass])
            @if($idAttr) id="{{ $idAttr }}" @endif
            alt="{{ $imgAlt }}"
            @if($styleAttr) style="{{ $styleAttr }}" @endif
        >
    </a>
@else
    <img
        src="{{ asset($normalized) }}"
        @class([$computedClass])
        @if($idAttr) id="{{ $idAttr }}" @endif
        alt="{{ $imgAlt }}"
        @if($styleAttr) style="{{ $styleAttr }}" @endif
    >
@endif

@if(session()->has('user_id'))
    <div class="notification-badge hidden" id="profileNotificationBadge">0</div>
@endif
