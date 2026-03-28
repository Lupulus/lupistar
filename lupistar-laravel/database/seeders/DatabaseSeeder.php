<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuteursTableSeeder::class,
            PaysTableSeeder::class,
            SousGenresTableSeeder::class,
            StudiosTableSeeder::class,
            MembresTableSeeder::class,
            FilmsTableSeeder::class,
            FilmsSousGenresTableSeeder::class,
            FilmsTempTableSeeder::class,
            FilmsTempSousGenresTableSeeder::class,
            MembresFilmsListTableSeeder::class,
            NotificationsTableSeeder::class,
            PasswordResetsTableSeeder::class,
            UserPreferencesTableSeeder::class,
        ]);
    }
}
