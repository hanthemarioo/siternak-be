<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FarmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        $admin1 = User::where('email', 'adminlayer@example.com')->first();
        $admin2 = User::where('email', 'admingrower@example.com')->first();

        $farm1 = Farm::create([
            'owner_id' => $superAdmin->id,
            'name' => 'Farm Layer Utama',
            'type' => 'layer',
            'location' => 'Ngawi',
        ]);

        $farm2 = Farm::create([
            'owner_id' => $superAdmin->id,
            'name' => 'Farm Grower Barat',
            'type' => 'grower',
            'location' => 'Madiun',
        ]);

        $admin1->update([
            'farm_id' => $farm1->id,
        ]);

        $admin2->update([
            'farm_id' => $farm2->id,
        ]);

        User::where('role', 'user_input')
            ->update([
                'farm_id' => $farm1->id,
            ]);
    }
}
