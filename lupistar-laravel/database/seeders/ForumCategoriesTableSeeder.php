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

        \DB::table('forum_categories')->insert([
            0 => [
                'id' => 1,
                'nom' => 'Global',
                'description' => 'Discussions générales sur tous les sujets',
                'couleur' => '#ff8c00',
                'icone' => 'fas fa-globe',
                'ordre' => 1,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            1 => [
                'id' => 2,
                'nom' => 'Film',
                'description' => 'Discussions sur les films',
                'couleur' => '#e74c3c',
                'icone' => 'fas fa-film',
                'ordre' => 2,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            2 => [
                'id' => 3,
                'nom' => 'Animation',
                'description' => 'Discussions sur les films d\'animation',
                'couleur' => '#3498db',
                'icone' => 'fas fa-magic',
                'ordre' => 3,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            3 => [
                'id' => 4,
                'nom' => 'Anime',
                'description' => 'Discussions sur les animés',
                'couleur' => '#9b59b6',
                'icone' => 'fas fa-star',
                'ordre' => 4,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            4 => [
                'id' => 5,
                'nom' => 'Série',
                'description' => 'Discussions sur les séries',
                'couleur' => '#2ecc71',
                'icone' => 'fas fa-tv',
                'ordre' => 5,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            5 => [
                'id' => 6,
                'nom' => 'Série d\'Animation',
                'description' => 'Discussions sur les séries d\'animation',
                'couleur' => '#f39c12',
                'icone' => 'fas fa-play-circle',
                'ordre' => 6,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
            6 => [
                'id' => 7,
                'nom' => 'Admin',
                'description' => 'Section administrative (réservée aux administrateurs)',
                'couleur' => '#34495e',
                'icone' => 'fas fa-shield-alt',
                'ordre' => 7,
                'admin_only' => 1,
                'active' => 1,
                'created_at' => '2025-10-06 10:39:10',
            ],
        ]);

    }
}
