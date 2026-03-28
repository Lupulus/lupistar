<?php

namespace App\Http\Controllers;

use App\Services\AccueilService;
use App\Services\ListeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListeApiController extends Controller
{
    public function __construct(
        private readonly AccueilService $accueilService,
        private readonly ListeService $listeService
    ) {}

    public function filters(Request $request): JsonResponse
    {
        $categories = $this->accueilService->categoriesOrderForUser(
            is_numeric($request->session()->get('user_id')) ? (int) $request->session()->get('user_id') : null
        );
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');

        return response()->json([
            'success' => true,
            'category' => $category,
            'studios' => $this->listeService->studiosForCategory($category),
            'years' => $this->listeService->yearsForCategory($category),
            'pays' => $this->listeService->paysForCategory($category),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $categories = $this->accueilService->categoriesOrderForUser(
            is_numeric($request->session()->get('user_id')) ? (int) $request->session()->get('user_id') : null
        );
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');

        return response()->json([
            'success' => true,
            'category' => $category,
            'stats' => $this->listeService->statsForCategory($category),
        ]);
    }

    public function films(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');
        $page = is_numeric($request->query('page')) ? (int) $request->query('page') : 1;

        $filters = $request->only(['recherche', 'studio', 'annee', 'note', 'pays', 'type', 'episodes']);
        $paginator = $this->listeService->paginatedFilmsForCategory($category, $filters, $page, 25);

        $myFilmIds = [];
        if ($userId) {
            $myFilmIds = DB::table('membres_films_list')
                ->where('membres_id', $userId)
                ->pluck('films_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        $html = view('Liste._films-grid', [
            'films' => $paginator->items(),
            'isMyList' => false,
            'myFilmIds' => $myFilmIds,
        ])->render();

        $paginationHtml = view('Liste._pagination', [
            'paginator' => $paginator,
        ])->render();

        return response()->json([
            'success' => true,
            'category' => $category,
            'html' => $html,
            'pagination_html' => $paginationHtml,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function myFilters(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');

        return response()->json([
            'success' => true,
            'category' => $category,
            'studios' => $this->listeService->studiosForUserCategory($userId, $category),
            'years' => $this->listeService->yearsForUserCategory($userId, $category),
            'pays' => $this->listeService->paysForUserCategory($userId, $category),
        ]);
    }

    public function myStats(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');

        return response()->json([
            'success' => true,
            'category' => $category,
            'stats' => $this->listeService->statsForUserCategory($userId, $category),
        ]);
    }

    public function myFilms(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $category = (string) $request->query('categorie', $categories[0] ?? 'Animation');
        $page = is_numeric($request->query('page')) ? (int) $request->query('page') : 1;

        $filters = $request->only(['recherche', 'studio', 'annee', 'note', 'pays', 'type', 'episodes']);
        $paginator = $this->listeService->paginatedFilmsForUserCategory($userId, $category, $filters, $page, 25);

        $html = view('Liste._films-grid', [
            'films' => $paginator->items(),
            'isMyList' => true,
            'myFilmIds' => [],
        ])->render();

        $paginationHtml = view('Liste._pagination', [
            'paginator' => $paginator,
        ])->render();

        return response()->json([
            'success' => true,
            'category' => $category,
            'html' => $html,
            'pagination_html' => $paginationHtml,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
