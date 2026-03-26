<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AuteursTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('auteurs')->delete();

        \DB::table('auteurs')->insert([
            0 => [
                'id' => 2,
                'nom' => 'Dean DeBlois',
                'categorie' => 'Animation',
            ],
            1 => [
                'id' => 4,
                'nom' => 'Jeff Nathanson',
                'categorie' => 'Film',
            ],
            2 => [
                'id' => 5,
                'nom' => 'Thierry Schiel',
                'categorie' => 'Animation',
            ],
            3 => [
                'id' => 6,
                'nom' => 'Osamu Tezuka',
                'categorie' => 'Anime',
            ],
            4 => [
                'id' => 7,
                'nom' => 'Konosuke Uda',
                'categorie' => 'Anime',
            ],
            5 => [
                'id' => 8,
                'nom' => 'Byron Howard',
                'categorie' => 'Animation',
            ],
            6 => [
                'id' => 9,
                'nom' => 'Nathan Greno',
                'categorie' => 'Animation',
            ],
            7 => [
                'id' => 10,
                'nom' => 'Chris Buck',
                'categorie' => 'Animation',
            ],
            8 => [
                'id' => 11,
                'nom' => 'George Scribner',
                'categorie' => 'Animation',
            ],
            9 => [
                'id' => 12,
                'nom' => 'David Hand',
                'categorie' => 'Animation',
            ],
            10 => [
                'id' => 13,
                'nom' => 'Hamilton Luske',
                'categorie' => 'Animation',
            ],
            11 => [
                'id' => 14,
                'nom' => 'James Algar',
                'categorie' => 'Animation',
            ],
            12 => [
                'id' => 15,
                'nom' => 'Ben Sharpsteen',
                'categorie' => 'Animation',
            ],
            13 => [
                'id' => 16,
                'nom' => 'David D. Hand',
                'categorie' => 'Animation',
            ],
            14 => [
                'id' => 17,
                'nom' => 'Brian Pimental',
                'categorie' => 'Animation',
            ],
            15 => [
                'id' => 18,
                'nom' => 'Norman Ferguson',
                'categorie' => 'Animation',
            ],
            16 => [
                'id' => 19,
                'nom' => 'Clyde Geronimi',
                'categorie' => 'Animation',
            ],
            17 => [
                'id' => 20,
                'nom' => 'John Kafka',
                'categorie' => 'Animation',
            ],
            18 => [
                'id' => 21,
                'nom' => 'Frank Nissen',
                'categorie' => 'Animation',
            ],
            19 => [
                'id' => 22,
                'nom' => 'Robin Budd',
                'categorie' => 'Animation',
            ],
        ]);

    }
}
