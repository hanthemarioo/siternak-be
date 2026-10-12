<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'phone_number' => '081111111111',
            'role' => 'super_admin',
            'password' => 'password',
        ]);

        $admin1 = User::create([
            'name' => 'Admin Layer',
            'email' => 'adminlayer@example.com',
            'phone_number' => '082222222222',
            'role' => 'admin',
            'password' => 'password',
            'created_by' => $superAdmin->id,
        ]);

        $admin2 = User::create([
            'name' => 'Admin Grower',
            'email' => 'admingrower@example.com',
            'phone_number' => '083333333333',
            'role' => 'admin',
            'password' => 'password',
            'created_by' => $superAdmin->id,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            User::create([
                'name' => "User Input {$i}",
                'email' => "userinput{$i}@example.com",
                'phone_number' => '08555555555' . $i,
                'role' => 'user_input',
                'password' => 'password',
                'created_by' => $superAdmin->id,
            ]);
        }
    }
}
