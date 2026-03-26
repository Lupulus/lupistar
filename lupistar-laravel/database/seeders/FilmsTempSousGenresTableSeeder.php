<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FilmsTempSousGenresTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('films_temp_sous_genres')->delete();

        \DB::table('films_temp_sous_genres')->insert([
            0 => [
                'film_temp_id' => 4,
                'sous_genre_id' => 2,
            ],
            1 => [
                'film_temp_id' => 6,
                'sous_genre_id' => 2,
            ],
            2 => [
                'film_temp_id' => 7,
                'sous_genre_id' => 2,
            ],
            3 => [
                'film_temp_id' => 8,
                'sous_genre_id' => 2,
            ],
            4 => [
                'film_temp_id' => 9,
                'sous_genre_id' => 2,
            ],
            5 => [
                'film_temp_id' => 12,
                'sous_genre_id' => 2,
            ],
            6 => [
                'film_temp_id' => 15,
                'sous_genre_id' => 2,
            ],
            7 => [
                'film_temp_id' => 17,
                'sous_genre_id' => 2,
            ],
            8 => [
                'film_temp_id' => 18,
                'sous_genre_id' => 2,
            ],
            9 => [
                'film_temp_id' => 22,
                'sous_genre_id' => 2,
            ],
            10 => [
                'film_temp_id' => 23,
                'sous_genre_id' => 2,
            ],
            11 => [
                'film_temp_id' => 9,
                'sous_genre_id' => 3,
            ],
            12 => [
                'film_temp_id' => 15,
                'sous_genre_id' => 4,
            ],
            13 => [
                'film_temp_id' => 12,
                'sous_genre_id' => 5,
            ],
            14 => [
                'film_temp_id' => 19,
                'sous_genre_id' => 5,
            ],
            15 => [
                'film_temp_id' => 23,
                'sous_genre_id' => 5,
            ],
            16 => [
                'film_temp_id' => 5,
                'sous_genre_id' => 11,
            ],
            17 => [
                'film_temp_id' => 6,
                'sous_genre_id' => 11,
            ],
            18 => [
                'film_temp_id' => 7,
                'sous_genre_id' => 11,
            ],
            19 => [
                'film_temp_id' => 8,
                'sous_genre_id' => 11,
            ],
            20 => [
                'film_temp_id' => 9,
                'sous_genre_id' => 11,
            ],
            21 => [
                'film_temp_id' => 11,
                'sous_genre_id' => 11,
            ],
            22 => [
                'film_temp_id' => 12,
                'sous_genre_id' => 11,
            ],
            23 => [
                'film_temp_id' => 14,
                'sous_genre_id' => 11,
            ],
            24 => [
                'film_temp_id' => 16,
                'sous_genre_id' => 11,
            ],
            25 => [
                'film_temp_id' => 17,
                'sous_genre_id' => 11,
            ],
            26 => [
                'film_temp_id' => 18,
                'sous_genre_id' => 11,
            ],
            27 => [
                'film_temp_id' => 19,
                'sous_genre_id' => 11,
            ],
            28 => [
                'film_temp_id' => 20,
                'sous_genre_id' => 11,
            ],
            29 => [
                'film_temp_id' => 21,
                'sous_genre_id' => 11,
            ],
            30 => [
                'film_temp_id' => 22,
                'sous_genre_id' => 11,
            ],
            31 => [
                'film_temp_id' => 23,
                'sous_genre_id' => 11,
            ],
            32 => [
                'film_temp_id' => 24,
                'sous_genre_id' => 11,
            ],
            33 => [
                'film_temp_id' => 9,
                'sous_genre_id' => 12,
            ],
            34 => [
                'film_temp_id' => 13,
                'sous_genre_id' => 12,
            ],
            35 => [
                'film_temp_id' => 22,
                'sous_genre_id' => 12,
            ],
            36 => [
                'film_temp_id' => 9,
                'sous_genre_id' => 13,
            ],
            37 => [
                'film_temp_id' => 13,
                'sous_genre_id' => 13,
            ],
            38 => [
                'film_temp_id' => 15,
                'sous_genre_id' => 13,
            ],
            39 => [
                'film_temp_id' => 18,
                'sous_genre_id' => 13,
            ],
            40 => [
                'film_temp_id' => 22,
                'sous_genre_id' => 13,
            ],
        ]);

    }
}
