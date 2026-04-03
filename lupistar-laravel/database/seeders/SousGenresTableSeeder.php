<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SousGenresTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('sous_genres')->delete();

        \DB::table('sous_genres')->insert([
            0 => [
                'id' => 1,
                'nom' => 'Action',
            ],
            1 => [
                'id' => 11,
                'nom' => 'Familial',
            ],
            2 => [
                'id' => 13,
                'nom' => 'Animation fantastique',
            ],
            3 => [
                'id' => 12,
                'nom' => 'Animation musicale',
            ],
            4 => [
                'id' => 2,
                'nom' => 'Aventure',
            ],
            5 => [
                'id' => 3,
                'nom' => 'Comédie',
            ],
            6 => [
                'id' => 24,
                'nom' => 'Conte',
            ],
            7 => [
                'id' => 23,
                'nom' => 'Cyberpunk',
            ],
            8 => [
                'id' => 25,
                'nom' => 'Documentaire',
            ],
            9 => [
                'id' => 4,
                'nom' => 'Drame',
            ],
            10 => [
                'id' => 5,
                'nom' => 'Fantastique',
            ],
            11 => [
                'id' => 20,
                'nom' => 'Guerre',
            ],
            12 => [
                'id' => 19,
                'nom' => 'Historique',
            ],
            13 => [
                'id' => 7,
                'nom' => 'Horreur',
            ],
            14 => [
                'id' => 15,
                'nom' => 'Mecha',
            ],
            15 => [
                'id' => 21,
                'nom' => 'Mystère',
            ],
            16 => [
                'id' => 10,
                'nom' => 'Policier',
            ],
            17 => [
                'id' => 9,
                'nom' => 'Romance',
            ],
            18 => [
                'id' => 6,
                'nom' => 'Science-fiction',
            ],
            19 => [
                'id' => 17,
                'nom' => 'Shôjo',
            ],
            20 => [
                'id' => 16,
                'nom' => 'Shônen',
            ],
            21 => [
                'id' => 14,
                'nom' => 'Slice of life',
            ],
            22 => [
                'id' => 18,
                'nom' => 'Sport',
            ],
            23 => [
                'id' => 22,
                'nom' => 'Super-héros',
            ],
            24 => [
                'id' => 8,
                'nom' => 'Thriller',
            ],
            25 => [
                'id' => 26,
                'nom' => 'Western',
            ],
        ]);

    }
}
