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
        $coops = Coop::with('flock')->get();

        $users = User::where('role', 'user_input')->get();

        foreach ($coops as $coop) {
            $farmUsers = $users->where('farm_id', $coop->flock->farm_id);

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
                ]);
            }
        }
    }
}
