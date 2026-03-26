<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ForumCommentsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('forum_comments')->delete();

        \DB::table('forum_comments')->insert([
            0 => [
                'id' => 1,
                'discussion_id' => 9,
                'author_id' => 1,
                'content' => 'Regarde, ça marche pas trop ?',
                'parent_id' => null,
                'edited_at' => null,
                'edited_by' => null,
                'created_at' => '2025-10-06 11:15:05',
            ],
            1 => [
                'id' => 2,
                'discussion_id' => 9,
                'author_id' => 1,
                'content' => 'ha si ?',
                'parent_id' => null,
                'edited_at' => null,
                'edited_by' => null,
                'created_at' => '2025-10-06 11:15:13',
            ],
        ]);

    }
}
