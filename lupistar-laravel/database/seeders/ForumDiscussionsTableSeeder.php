<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ForumDiscussionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('forum_discussions')->delete();

        \DB::table('forum_discussions')->insert([
            0 => [
                'id' => 1,
                'titre' => 'Générale',
                'description' => 'Discussion générale ouverte à tous',
                'category_id' => 1,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            1 => [
                'id' => 2,
                'titre' => 'Bug/Rapport - Global',
                'description' => 'Signalez ici les bugs et problèmes généraux du site',
                'category_id' => 1,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            2 => [
                'id' => 3,
                'titre' => 'Bug/Rapport - Films',
                'description' => 'Signalez ici les bugs liés aux films',
                'category_id' => 2,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            3 => [
                'id' => 4,
                'titre' => 'Bug/Rapport - Animations',
                'description' => 'Signalez ici les bugs liés aux animations',
                'category_id' => 3,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            4 => [
                'id' => 5,
                'titre' => 'Bug/Rapport - Animés',
                'description' => 'Signalez ici les bugs liés aux animés',
                'category_id' => 4,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            5 => [
                'id' => 6,
                'titre' => 'Bug/Rapport - Séries',
                'description' => 'Signalez ici les bugs liés aux séries',
                'category_id' => 5,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            6 => [
                'id' => 7,
                'titre' => 'Bug/Rapport - Séries d\'Animation',
                'description' => 'Signalez ici les bugs liés aux séries d\'animation',
                'category_id' => 6,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            7 => [
                'id' => 8,
                'titre' => 'Annonces Administratives',
                'description' => 'Annonces importantes de l\'équipe administrative',
                'category_id' => 7,
                'author_id' => 1,
                'pinned' => 1,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 10:39:10',
                'updated_at' => '2025-10-06 10:39:10',
            ],
            8 => [
                'id' => 9,
                'titre' => 'test',
                'description' => 'test',
                'category_id' => 1,
                'author_id' => 1,
                'pinned' => 0,
                'locked' => 0,
                'views' => 0,
                'last_comment_at' => null,
                'last_comment_by' => null,
                'created_at' => '2025-10-06 11:14:44',
                'updated_at' => '2025-10-06 11:15:13',
            ],
        ]);

    }
}
