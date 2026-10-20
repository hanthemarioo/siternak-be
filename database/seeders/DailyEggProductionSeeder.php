<?php

namespace Database\Seeders;

use App\Models\DailyEggProduction;
use App\Models\DailyFlockRecord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyEggProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = DailyFlockRecord::whereHas('coop.flock.farm', function ($q) {
            $q->where('type', 'layer');
        })->with('coop')->get();

        foreach ($records as $record) {

            $population =
                $record->coop->initial_population -
                $record->mortality_count -
                $record->culling_count;

            $marketableCount = rand(
                intval($population * 0.75),
                intval($population * 0.90)
            );

            $sortedCount = rand(10, 40);
            $spoiledCount = rand(1, 10);
            $brokenCount = rand(1, 15);

            DailyEggProduction::create([
                'daily_flock_record_id' => $record->id,

                'marketable_eggs_count' => $marketableCount,
                'marketable_eggs_weight_kg' => round($marketableCount * 0.06, 3),

                'sorted_eggs_count' => $sortedCount,
                'sorted_eggs_weight_kg' => round($sortedCount * 0.06, 3),

                'spoiled_eggs_count' => $spoiledCount,
                'spoiled_eggs_weight_kg' => round($spoiledCount * 0.06, 3),

                'broken_eggs_count' => $brokenCount,
                'broken_eggs_weight_kg' => round($brokenCount * 0.06, 3),
            ]);
        }
    }
}
