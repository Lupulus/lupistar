<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MembresFilmsListTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('membres_films_list')->delete();

        \DB::table('membres_films_list')->insert([
            0 => [
                'membres_id' => 1,
                'films_id' => 2,
                'note' => 10,
            ],
            1 => [
                'membres_id' => 1,
                'films_id' => 4,
                'note' => 9,
            ],
            2 => [
                'membres_id' => 1,
                'films_id' => 5,
                'note' => 6,
            ],
            3 => [
                'membres_id' => 1,
                'films_id' => 10,
                'note' => 10,
            ],
            4 => [
                'membres_id' => 3,
                'films_id' => 5,
                'note' => 3,
            ],
        ]);

    }
}
