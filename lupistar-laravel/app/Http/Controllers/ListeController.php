<?php

namespace App\Http\Controllers;

use App\Services\AccueilService;
use App\Services\ListeService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListeController extends Controller
{
    public function __construct(
        private readonly AccueilService $accueilService,
        private readonly ListeService $listeService
    ) {}

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $categories = $this->accueilService->categoriesOrderForUser(
            $userId
        );

        $category = $request->query('categorie', $categories[0] ?? 'Animation');

        $years = $this->listeService->yearsForCategory($category);
        $studios = $this->listeService->studiosForCategory($category);
        $pays = $this->listeService->paysForCategory($category);

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

        return view('Liste.index', [
            'title' => 'Liste des films',
            'categories' => $categories,
            'category' => $category,
            'years' => $years,
            'studios' => $studios,
            'pays' => $pays,
            'paginator' => $paginator,
            'myFilmIds' => $myFilmIds,
        ]);
    }

    public function myList(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if (! $userId) {
            $categories = $this->accueilService->categoriesOrderForUser(null);
            $category = $request->query('categorie', $categories[0] ?? 'Animation');

            $empty = new LengthAwarePaginator([], 0, 25, 1);

            return view('Liste.my-list', [
                'title' => 'Ma Liste',
                'categories' => $categories,
                'category' => $category,
                'years' => [],
                'studios' => [],
                'pays' => [],
                'paginator' => $empty,
                'notLoggedIn' => true,
            ]);
        }

        $categories = $this->accueilService->categoriesOrderForUser($userId);
        $category = $request->query('categorie', $categories[0] ?? 'Animation');

        $years = $this->listeService->yearsForUserCategory($userId, $category);
        $studios = $this->listeService->studiosForUserCategory($userId, $category);
        $pays = $this->listeService->paysForUserCategory($userId, $category);

        $page = is_numeric($request->query('page')) ? (int) $request->query('page') : 1;
        $filters = $request->only(['recherche', 'studio', 'annee', 'note', 'pays', 'type', 'episodes']);
        $paginator = $this->listeService->paginatedFilmsForUserCategory($userId, $category, $filters, $page, 25);

        return view('Liste.my-list', [
            'title' => 'Ma Liste',
            'categories' => $categories,
            'category' => $category,
            'years' => $years,
            'studios' => $studios,
            'pays' => $pays,
            'paginator' => $paginator,
        ]);
    }
}
