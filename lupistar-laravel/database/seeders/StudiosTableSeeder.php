<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StudiosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('studios')->delete();

        \DB::table('studios')->insert([
            0 => [
                'id' => 1,
                'nom' => 'Inconnu',
                'categorie' => 'Film,Série,Série d\'Animation,Animation,Anime',
            ],
            1 => [
                'id' => 2,
                'nom' => 'DreamWorks Animation',
                'categorie' => 'Animation,Film',
            ],
            2 => [
                'id' => 5,
                'nom' => 'Studiocanal',
                'categorie' => 'Animation',
            ],
            3 => [
                'id' => 6,
                'nom' => 'Tezuka Productions',
                'categorie' => 'Animation',
            ],
            4 => [
                'id' => 7,
                'nom' => 'Toei Animation',
                'categorie' => 'Anime',
            ],
            5 => [
                'id' => 8,
                'nom' => 'Walt Disney',
                'categorie' => 'Animation,Film',
            ],
        ]);

    }
}
