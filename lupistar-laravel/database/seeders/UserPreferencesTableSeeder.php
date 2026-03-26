<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserPreferencesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('user_preferences')->delete();

        \DB::table('user_preferences')->insert([
            0 => [
                'id' => 1,
                'user_id' => 1,
                'preference_type' => 'categories_order',
                'preference_value' => '["Animation","Anime","S\\u00e9rie d\'Animation","Film","S\\u00e9rie"]',
                'created_at' => '2025-10-05 11:11:16',
                'updated_at' => '2025-10-05 17:09:12',
            ],
            1 => [
                'id' => 2,
                'user_id' => 2,
                'preference_type' => 'categories_order',
                'preference_value' => '["Animation", "Anime", "Série d\'Animation", "Film", "Série"]',
                'created_at' => '2025-10-05 11:11:16',
                'updated_at' => '2025-10-05 11:11:16',
            ],
            2 => [
                'id' => 3,
                'user_id' => 3,
                'preference_type' => 'categories_order',
                'preference_value' => '["Animation", "Anime", "Série d\'Animation", "Film", "Série"]',
                'created_at' => '2025-10-05 11:11:16',
                'updated_at' => '2025-10-05 11:11:16',
            ],
            3 => [
                'id' => 4,
                'user_id' => 4,
                'preference_type' => 'categories_order',
                'preference_value' => '["Animation", "Anime", "Série d\'Animation", "Film", "Série"]',
                'created_at' => '2025-10-05 11:11:16',
                'updated_at' => '2025-10-05 11:11:16',
            ],
        ]);

    }
}
