@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-admin.css') }}">
@endsection

@section('content')
    <div class="admin-section" style="padding:20px;max-width:1100px;margin:0 auto;">
        <h2>Propositions en attente</h2>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Année</th>
                    <th>Studio</th>
                    <th>Auteur</th>
                    <th>Pays</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($propositions as $p)
                    <tr>
                        <td>{{ $p->nom_film }}</td>
                        <td>{{ $p->categorie }}</td>
                        <td>{{ $p->date_sortie }}</td>
                        <td>{{ $p->studio_nom }}</td>
                        <td>{{ $p->auteur_nom }}</td>
                        <td>{{ $p->pays_nom }}</td>
                        <td>
                            <form action="{{ route('administration.propositions.approve', ['id' => $p->id]) }}" method="post" style="display:inline">
                                @csrf
                                <input type="hidden" name="nom_film" value="{{ $p->nom_film }}">
                                <input type="hidden" name="categorie" value="{{ $p->categorie }}">
                                <input type="hidden" name="description" value="{{ $p->description }}">
                                <input type="hidden" name="date_sortie" value="{{ $p->date_sortie }}">
                                <input type="hidden" name="ordre_suite" value="{{ $p->ordre_suite }}">
                                <input type="hidden" name="saison" value="{{ $p->saison }}">
                                <input type="hidden" name="nbrEpisode" value="{{ $p->nbrEpisode }}">
                                <button type="submit">Approuver</button>
                            </form>
                            <form action="{{ route('administration.propositions.reject', ['id' => $p->id]) }}" method="post" style="display:inline;margin-left:8px">
                                @csrf
                                <input type="text" name="commentaire_admin" placeholder="Commentaire" />
                                <button type="submit">Rejeter</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Aucune proposition</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
