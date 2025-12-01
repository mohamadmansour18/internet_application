<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         User::query()->create([
            'name' => 'admin',
            'email' => 'obadawork912@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER->value,
            'last_login_at' => now(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'officer one',
            'email' => 'officerOne@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::OFFICER->value,
            'last_login_at' => now(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

         StaffProfile::query()->create([
             'user_id' => $user->id,
             'agency_id' => 1,
         ]);
    }
}
