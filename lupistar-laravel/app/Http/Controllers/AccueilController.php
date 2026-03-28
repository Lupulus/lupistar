<?php

namespace App\Http\Controllers;

use App\Services\AccueilService;
use Illuminate\Http\Request;

class AccueilController extends Controller
{
    public function __construct(private readonly AccueilService $accueilService) {}

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $recentFilmsByCategory = $this->accueilService->recentFilmsByCategory($categories);

        return view('Accueil.index', [
            'categories' => $categories,
            'recentFilmsByCategory' => $recentFilmsByCategory,
        ]);
    }
}
