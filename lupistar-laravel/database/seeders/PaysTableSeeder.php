<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaysTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('pays')->delete();

        \DB::table('pays')->insert([
            0 => [
                'id' => 9,
                'nom' => 'Allemagne 🇩🇪',
            ],
            1 => [
                'id' => 22,
                'nom' => 'Argentine 🇦🇷',
            ],
            2 => [
                'id' => 12,
                'nom' => 'Australie 🇦🇺',
            ],
            3 => [
                'id' => 18,
                'nom' => 'Belgique 🇧🇪',
            ],
            4 => [
                'id' => 15,
                'nom' => 'Brésil 🇧🇷',
            ],
            5 => [
                'id' => 8,
                'nom' => 'Canada 🇨🇦',
            ],
            6 => [
                'id' => 4,
                'nom' => 'Chine 🇨🇳',
            ],
            7 => [
                'id' => 6,
                'nom' => 'Corée du Sud 🇰🇷',
            ],
            8 => [
                'id' => 13,
                'nom' => 'Espagne 🇪🇸',
            ],
            9 => [
                'id' => 1,
                'nom' => 'États-Unis 🇺🇸',
            ],
            10 => [
                'id' => 5,
                'nom' => 'France 🇫🇷',
            ],
            11 => [
                'id' => 3,
                'nom' => 'Inde 🇮🇳',
            ],
            12 => [
                'id' => 11,
                'nom' => 'Italie 🇮🇹',
            ],
            13 => [
                'id' => 2,
                'nom' => 'Japon 🇯🇵',
            ],
            14 => [
                'id' => 14,
                'nom' => 'Mexique 🇲🇽',
            ],
            15 => [
                'id' => 21,
                'nom' => 'Nouvelle-Zélande 🇳🇿',
            ],
            16 => [
                'id' => 16,
                'nom' => 'Pays-Bas 🇳🇱',
            ],
            17 => [
                'id' => 23,
                'nom' => 'Philippines 🇵🇭',
            ],
            18 => [
                'id' => 25,
                'nom' => 'Pologne 🇵🇱',
            ],
            19 => [
                'id' => 7,
                'nom' => 'Royaume-Uni 🇬🇧',
            ],
            20 => [
                'id' => 10,
                'nom' => 'Russie 🇷🇺',
            ],
            21 => [
                'id' => 17,
                'nom' => 'Suède 🇸🇪',
            ],
            22 => [
                'id' => 19,
                'nom' => 'Taiwan 🇹🇼',
            ],
            23 => [
                'id' => 20,
                'nom' => 'Thaïlande 🇹🇭',
            ],
            24 => [
                'id' => 24,
                'nom' => 'Vietnam 🇻🇳',
            ],
            25 => [
                'id' => 26,
                'nom' => 'Afrique du Sud 🇿🇦',
            ],
            26 => [
                'id' => 27,
                'nom' => 'Danemark 🇩🇰',
            ],
            27 => [
                'id' => 28,
                'nom' => 'Autriche 🇦🇹',
            ],
            28 => [
                'id' => 29,
                'nom' => 'Finlande 🇫🇮',
            ],
            29 => [
                'id' => 30,
                'nom' => 'Hong Kong 🇭🇰',
            ],
            30 => [
                'id' => 31,
                'nom' => 'Indonésie 🇮🇩',
            ],
            31 => [
                'id' => 32,
                'nom' => 'Irlande 🇮🇪',
            ],
            32 => [
                'id' => 33,
                'nom' => 'Norvège 🇳🇴',
            ],
            33 => [
                'id' => 34,
                'nom' => 'République tchèque 🇨🇿',
            ],
            34 => [
                'id' => 35,
                'nom' => 'Suisse 🇨🇭',
            ],
        ]);

    }
}
