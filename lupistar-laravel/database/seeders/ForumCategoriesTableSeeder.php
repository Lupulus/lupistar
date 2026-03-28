<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ForumCategoriesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('forum_categories')->delete();

        $now = now();

        \DB::table('forum_categories')->insert([
            [
                'id' => 1,
                'nom' => 'Discussions Générales',
                'description' => 'Cinéma, séries, nouveautés…',
                'couleur' => '#ff8c00',
                'icone' => 'fas fa-comments',
                'ordre' => 1,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'id' => 2,
                'nom' => 'Critique et avis',
                'description' => 'Reviews, critiques, notes et avis des membres.',
                'couleur' => '#3498db',
                'icone' => 'fas fa-star',
                'ordre' => 2,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'id' => 3,
                'nom' => 'Suggestion & Reco...',
                'description' => 'Films à découvrir, recommandations d’autres utilisateurs.',
                'couleur' => '#2ecc71',
                'icone' => 'fas fa-lightbulb',
                'ordre' => 3,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'id' => 4,
                'nom' => 'Support / Questions',
                'description' => 'Bugs du site, questions techniques.',
                'couleur' => '#e74c3c',
                'icone' => 'fas fa-life-ring',
                'ordre' => 4,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'id' => 5,
                'nom' => 'Admin',
                'description' => 'Section administrative (réservée aux administrateurs)',
                'couleur' => '#34495e',
                'icone' => 'fas fa-shield-alt',
                'ordre' => 99,
                'admin_only' => 1,
                'active' => 1,
                'created_at' => $now,
            ],
        ]);
    }
}
