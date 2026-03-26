@extends('layouts.site')

@section('content')
    <div style="padding:20px;">
        <h1>Membres</h1>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:8px;border-bottom:1px solid #333;">ID</th>
                    <th style="text-align:left;padding:8px;border-bottom:1px solid #333;">Pseudo</th>
                    <th style="text-align:left;padding:8px;border-bottom:1px solid #333;">Titre</th>
                    <th style="text-align:left;padding:8px;border-bottom:1px solid #333;">Créé le</th>
                </tr>
            </thead>
            <tbody>
                @forelse($membres as $m)
                <tr>
                    <td style="padding:8px;">{{ $m->id }}</td>
                    <td style="padding:8px;">{{ $m->username }}</td>
                    <td style="padding:8px;">{{ $m->titre }}</td>
                    <td style="padding:8px;">{{ $m->date_creation }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:8px;">Aucun membre</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

