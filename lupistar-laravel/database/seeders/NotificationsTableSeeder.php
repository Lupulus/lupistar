<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('notifications')->delete();

        \DB::table('notifications')->insert([
            0 => [
                'id' => 19,
                'user_id' => 3,
                'titre' => 'Titre modifié',
                'message' => 'Votre titre a été modifié par un administrateur : Membre',
                'type' => 'title_change_admin',
                'lu' => 0,
                'date_creation' => '2025-10-04 00:36:07',
            ],
            1 => [
                'id' => 20,
                'user_id' => 3,
                'titre' => 'Titre modifié',
                'message' => 'Votre titre a été modifié par un administrateur : Admin',
                'type' => 'title_change_admin',
                'lu' => 0,
                'date_creation' => '2025-10-04 00:36:21',
            ],
            2 => [
                'id' => 21,
                'user_id' => 3,
                'titre' => 'Titre modifié',
                'message' => 'Votre titre a été modifié par un administrateur : Membre',
                'type' => 'title_change_admin',
                'lu' => 0,
                'date_creation' => '2025-10-04 00:37:24',
            ],
            3 => [
                'id' => 22,
                'user_id' => 3,
                'titre' => 'Titre modifié',
                'message' => 'Votre titre a été modifié par un administrateur : Admin',
                'type' => 'title_change_admin',
                'lu' => 0,
                'date_creation' => '2025-10-04 00:47:15',
            ],
        ]);

    }
}
