<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ForumModerationsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('forum_moderations')->delete();

    }
}
