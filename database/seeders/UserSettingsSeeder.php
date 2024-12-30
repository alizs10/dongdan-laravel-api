<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserSetting;

class UserSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::all()->each(function ($user) {
            UserSetting::create([
                'user_id' => $user->id,
                'show_as_me' => true,
            ]);
        });
    }
}
