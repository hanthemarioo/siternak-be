<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\Flock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $farm = Farm::where('name', 'Farm Layer Utama')->first();

        Flock::create([
            'farm_id' => $farm->id,
            'name' => 'Flock Layer A',
            'house_code' => 'LA-01',
            'age_in_day' => 120,
            'strain' => 'Hy-Line Brown',
            'start_date' => now()->subDays(120),
            'status' => 'active',
        ]);

        Flock::create([
            'farm_id' => $farm->id,
            'name' => 'Flock Layer B',
            'house_code' => 'LB-01',
            'age_in_day' => 90,
            'strain' => 'Lohmann Brown',
            'start_date' => now()->subDays(90),
            'status' => 'active',
        ]);

        $growerFarm = Farm::where('name', 'Farm Grower Barat')->first();
        if ($growerFarm) {
            Flock::create([
                'farm_id' => $growerFarm->id,
                'name' => 'Flock Grower G1',
                'house_code' => 'GB-01',
                'age_in_day' => 45,
                'strain' => 'Lohmann Brown',
                'start_date' => now()->subDays(45),
                'status' => 'active',
            ]);
        }
    }
}
