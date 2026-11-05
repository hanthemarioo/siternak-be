<?php

namespace Database\Seeders;

use App\Models\Coop;
use App\Models\DailyFlockRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyFlockRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coops = Coop::with('flock.farm')->get();

        $users = User::where('role', 'user_input')->get();

        foreach ($coops as $coop) {
            $farmUsers = $users->where('farm_id', $coop->flock->farm_id);
            $isGrower = $coop->flock->farm->type === 'grower';

            for ($d = 29; $d >= 0; $d--) {

                $date = Carbon::now()->subDays($d);
                $creator = $farmUsers->isNotEmpty() ? $farmUsers->random() : $users->random();

                DailyFlockRecord::create([
                    'coop_id' => $coop->id,
                    'created_by' => $creator->id,
                    'date' => $date->toDateString(),
                    'feed_kg' => rand(450, 650),
                    'water_l' => rand(900, 1300),
                    'avg_body_weight_kg' => rand(150, 220) / 100,
                    'mortality_count' => rand(0, 5),
                    'culling_count' => rand(0, 3),
                    'temperature_c' => $isGrower ? rand(265, 315) / 10 : null,
                    'uniformity_percent' => $isGrower ? rand(80, 92) : null,
                    'feed_name' => $isGrower ? 'BR-1 Crumble' : 'Layer Komplit L-1',
                    'medicine_vitamin' => $d % 3 === 0 ? 'Vita Stress' : null,
                    'vaccine' => $d === 14 ? 'ND Lasota' : null,
                    'notes' => $isGrower ? 'Blower aktif normal, sekam kering' : 'Kondisi kandang stabil',
                ]);
            }
        }
    }
}
