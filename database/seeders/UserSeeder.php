<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'root',
                'username' => 'root',
                'email' => 'root@root.com',
                'email_verified_at' => now(),
                'permission' => 'superadmin',
                'is_active' => true,
                'active_device' => 'Chrome di Windows',
                'password' => Hash::make('/'),
                'password_changed_at' => now(),
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
