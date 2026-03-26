<?php

namespace App\Http\Controllers;

use App\Models\Membre;

class MembresController extends Controller
{
    public function index()
    {
        $membres = Membre::query()
            ->select(['id', 'username', 'titre', 'date_creation'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('Membres.index', [
            'title' => 'Membres',
            'membres' => $membres,
        ]);
    }
}
