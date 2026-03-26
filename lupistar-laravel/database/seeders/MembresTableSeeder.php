<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MembresTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('membres')->delete();

        \DB::table('membres')->insert([
            0 => [
                'id' => 1,
                'username' => 'Lupulus',
                'password' => '$2y$10$lxXNOQrEJPph3b/XzBdyAuAQAJ3D/ivrfKDNKHlITRvZKerfuXxLi',
                'titre' => 'Super-Admin',
                'email' => 'ailo.volle@gmail.com',
                'photo_profil' => 'img/img-profile/Lupulus-profile.png',
                'restriction' => 'Aucune',
                'avertissements' => 0,
                'recompenses' => 0,
                'politique_acceptee' => 1,
                'max_films_liste_atteint' => 0,
                'max_films_approuves_atteint' => 0,
                'demande_promotion' => 0,
                'date_derniere_verification' => '2025-10-07 12:06:05',
                'date_creation' => '2025-10-07 12:06:05',
            ],
            1 => [
                'id' => 2,
                'username' => 'mememdptest',
                'password' => '$2y$10$1L3VGh743bVadLDPNpUfreeXUA0okTbzpr1W3Ec/vDFTzHSVTBFn.',
                'titre' => 'Amateur',
                'email' => 'memmdptest@gmail.com',
                'photo_profil' => 'img/img-profile/profil.png',
                'restriction' => 'Aucune',
                'avertissements' => 0,
                'recompenses' => 0,
                'politique_acceptee' => 1,
                'max_films_liste_atteint' => 0,
                'max_films_approuves_atteint' => 0,
                'demande_promotion' => 0,
                'date_derniere_verification' => '2025-10-07 12:06:05',
                'date_creation' => '2025-10-07 12:06:05',
            ],
            2 => [
                'id' => 3,
                'username' => 'Clem',
                'password' => '$2y$10$7iyeTU6x0D8SRHTTz8H6rugA5GJQNI.9Qq7g/jCudlgld8RbksZ9e',
                'titre' => 'Admin',
                'email' => 'clementvolle@gmail.com',
                'photo_profil' => 'img/img-profile/Clem-profile.png',
                'restriction' => 'Aucune',
                'avertissements' => 0,
                'recompenses' => 0,
                'politique_acceptee' => 1,
                'max_films_liste_atteint' => 0,
                'max_films_approuves_atteint' => 0,
                'demande_promotion' => 0,
                'date_derniere_verification' => '2025-10-07 12:06:05',
                'date_creation' => '2025-10-07 12:06:05',
            ],
            3 => [
                'id' => 4,
                'username' => 'GB84',
                'password' => '$2y$10$DJWoy7kTwYqEL4Ahh1GXKubjmultJULu4zJh7YbVciDd0JIYwAfDG',
                'titre' => 'Admin',
                'email' => 'minecraft.t.n.t84@gmail.com',
                'photo_profil' => 'img/img-profile/GB84-profile.png',
                'restriction' => 'Aucune',
                'avertissements' => 0,
                'recompenses' => 0,
                'politique_acceptee' => 1,
                'max_films_liste_atteint' => 0,
                'max_films_approuves_atteint' => 0,
                'demande_promotion' => 0,
                'date_derniere_verification' => '2025-10-07 12:06:05',
                'date_creation' => '2025-10-07 12:06:05',
            ],
        ]);

    }
}
