<?php

namespace App\Http\Controllers;

use App\Services\AccueilService;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccueilController extends Controller
{
    public function __construct(
        private readonly AccueilService $accueilService,
        private readonly RecommendationService $recommendationService
    ) {}

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $heroFilm = $this->accueilService->heroFilm();
        $recommendations = $this->recommendationService->recommendations($userId, false, 18);
        $recentFilms = $this->accueilService->recentFilmsAll(30);
        $voyage = $this->accueilService->voyageForDay(5, 8);

        return view('Accueil.index', [
            'heroFilm' => $heroFilm,
            'recommendations' => $recommendations,
            'recentFilms' => $recentFilms,
            'voyagePays' => $voyage['pays'] ?? null,
            'voyageFilms' => $voyage['films'] ?? collect(),
            'isLoggedIn' => (bool) $userId,
        ]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $includeSeen = $request->query('include_seen') === '1';
        $seed = is_numeric($request->query('seed')) ? (int) $request->query('seed') : null;

        $items = $this->recommendationService->recommendations($userId, $includeSeen, 18, $seed);

        $html = view('Accueil._recommendations', [
            'recommendations' => $items,
            'isLoggedIn' => (bool) $userId,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
