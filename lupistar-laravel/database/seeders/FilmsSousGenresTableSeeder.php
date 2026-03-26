<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FilmsSousGenresTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('films_sous_genres')->delete();

        \DB::table('films_sous_genres')->insert([
            0 => [
                'film_id' => 2,
                'sous_genre_id' => 2,
            ],
            1 => [
                'film_id' => 4,
                'sous_genre_id' => 2,
            ],
            2 => [
                'film_id' => 6,
                'sous_genre_id' => 2,
            ],
            3 => [
                'film_id' => 7,
                'sous_genre_id' => 2,
            ],
            4 => [
                'film_id' => 8,
                'sous_genre_id' => 2,
            ],
            5 => [
                'film_id' => 9,
                'sous_genre_id' => 2,
            ],
            6 => [
                'film_id' => 10,
                'sous_genre_id' => 2,
            ],
            7 => [
                'film_id' => 13,
                'sous_genre_id' => 2,
            ],
            8 => [
                'film_id' => 16,
                'sous_genre_id' => 2,
            ],
            9 => [
                'film_id' => 18,
                'sous_genre_id' => 2,
            ],
            10 => [
                'film_id' => 19,
                'sous_genre_id' => 2,
            ],
            11 => [
                'film_id' => 23,
                'sous_genre_id' => 2,
            ],
            12 => [
                'film_id' => 24,
                'sous_genre_id' => 2,
            ],
            13 => [
                'film_id' => 10,
                'sous_genre_id' => 3,
            ],
            14 => [
                'film_id' => 16,
                'sous_genre_id' => 4,
            ],
            15 => [
                'film_id' => 9,
                'sous_genre_id' => 5,
            ],
            16 => [
                'film_id' => 13,
                'sous_genre_id' => 5,
            ],
            17 => [
                'film_id' => 20,
                'sous_genre_id' => 5,
            ],
            18 => [
                'film_id' => 24,
                'sous_genre_id' => 5,
            ],
            19 => [
                'film_id' => 9,
                'sous_genre_id' => 9,
            ],
            20 => [
                'film_id' => 2,
                'sous_genre_id' => 11,
            ],
            21 => [
                'film_id' => 5,
                'sous_genre_id' => 11,
            ],
            22 => [
                'film_id' => 6,
                'sous_genre_id' => 11,
            ],
            23 => [
                'film_id' => 7,
                'sous_genre_id' => 11,
            ],
            24 => [
                'film_id' => 8,
                'sous_genre_id' => 11,
            ],
            25 => [
                'film_id' => 9,
                'sous_genre_id' => 11,
            ],
            26 => [
                'film_id' => 10,
                'sous_genre_id' => 11,
            ],
            27 => [
                'film_id' => 11,
                'sous_genre_id' => 11,
            ],
            28 => [
                'film_id' => 12,
                'sous_genre_id' => 11,
            ],
            29 => [
                'film_id' => 13,
                'sous_genre_id' => 11,
            ],
            30 => [
                'film_id' => 15,
                'sous_genre_id' => 11,
            ],
            31 => [
                'film_id' => 17,
                'sous_genre_id' => 11,
            ],
            32 => [
                'film_id' => 18,
                'sous_genre_id' => 11,
            ],
            33 => [
                'film_id' => 19,
                'sous_genre_id' => 11,
            ],
            34 => [
                'film_id' => 20,
                'sous_genre_id' => 11,
            ],
            35 => [
                'film_id' => 21,
                'sous_genre_id' => 11,
            ],
            36 => [
                'film_id' => 22,
                'sous_genre_id' => 11,
            ],
            37 => [
                'film_id' => 23,
                'sous_genre_id' => 11,
            ],
            38 => [
                'film_id' => 24,
                'sous_genre_id' => 11,
            ],
            39 => [
                'film_id' => 25,
                'sous_genre_id' => 11,
            ],
            40 => [
                'film_id' => 10,
                'sous_genre_id' => 12,
            ],
            41 => [
                'film_id' => 14,
                'sous_genre_id' => 12,
            ],
            42 => [
                'film_id' => 23,
                'sous_genre_id' => 12,
            ],
            43 => [
                'film_id' => 10,
                'sous_genre_id' => 13,
            ],
            44 => [
                'film_id' => 14,
                'sous_genre_id' => 13,
            ],
            45 => [
                'film_id' => 16,
                'sous_genre_id' => 13,
            ],
            46 => [
                'film_id' => 19,
                'sous_genre_id' => 13,
            ],
            47 => [
                'film_id' => 23,
                'sous_genre_id' => 13,
            ],
        ]);

    }
}
