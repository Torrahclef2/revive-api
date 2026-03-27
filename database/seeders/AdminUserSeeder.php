<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@revive.app'],
            [
                'name'       => 'Admin',
                'username'   => 'admin',
                'email'      => 'admin@revive.app',
                'password'   => Hash::make('Admin@1234'),
                'is_admin'   => true,
                'is_verified'=> true,
            ]
        );
    }
}
