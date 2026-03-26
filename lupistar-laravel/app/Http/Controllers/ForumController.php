<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $categories = ForumCategory::query()
            ->orderBy('nom')
            ->get();

        return view('Forum.index', [
            'title' => 'Forum',
            'categories' => $categories,
        ]);
    }
}
