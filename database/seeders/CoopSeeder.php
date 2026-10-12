<?php

namespace Database\Seeders;

use App\Models\Coop;
use App\Models\Flock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flockA = Flock::where('name', 'Flock Layer A')->first();
        $flockB = Flock::where('name', 'Flock Layer B')->first();

        Coop::create([
            'flock_id' => $flockA->id,
            'name' => 'Kandang A1',
            'initial_population' => 5000,
        ]);

        Coop::create([
            'flock_id' => $flockA->id,
            'name' => 'Kandang A2',
            'initial_population' => 4800,
        ]);

        Coop::create([
            'flock_id' => $flockB->id,
            'name' => 'Kandang B1',
            'initial_population' => 4500,
        ]);
    }
}
