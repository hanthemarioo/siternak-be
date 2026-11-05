<?php

namespace App\Services\Reports;

use App\Models\DailyFlockRecord;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FlockReportService
{
    public function grower(array $filters): array
    {
        $records = $this->records($filters, 'grower', false);
        $populationByCoopDate = $this->populationByCoopDate($records, $filters);

        return $records
            ->groupBy(fn ($record) => $record->date->toDateString())
            ->map(function (Collection $dateRecords, string $date) use ($populationByCoopDate) {
                $population = $this->populationForDate($dateRecords, $date, $populationByCoopDate);
                $mortality = (int) $dateRecords->sum('mortality_count');
                $feedKg = (float) $dateRecords->sum('feed_kg');
                $waterL = (float) $dateRecords->sum('water_l');
                $avgBodyWeightKg = $this->weightedBodyWeightKg($dateRecords, $date, $populationByCoopDate);
                $avgTemperature = $this->nullableAverage($dateRecords, 'temperature_c', 1);
                $avgUniformity = $this->nullableAverage($dateRecords, 'uniformity_percent', 2);

                return [
                    'date' => $date,
                    'age_week' => $this->sameAge($dateRecords, $date, 'week'),
                    'population' => $population,
                    'mortality' => $mortality,
                    'culling' => (int) $dateRecords->sum('culling_count'),
                    'mortality_percent' => $this->percentage($mortality, $population),
                    'feed_total_kg' => round($feedKg, 2),
                    'feed_gram_per_bird' => $this->ratio($feedKg * 1000, $population),
                    'feed_standard_gram_per_bird' => null,
                    'water_total_l' => round($waterL, 2),
                    'water_ml_per_bird' => $this->ratio($waterL * 1000, $population),
                    'actual_body_weight_g' => round($avgBodyWeightKg * 1000, 2),
                    'standard_body_weight_g' => null,
                    'temperature_c' => $avgTemperature,
                    'actual_uniformity_percent' => $avgUniformity,
                    'standard_uniformity_percent' => null,
                    'feed_name' => $this->mergedText($dateRecords, 'feed_name'),
                    'medicine_vitamin' => $this->mergedText($dateRecords, 'medicine_vitamin'),
                    'vaccine' => $this->mergedText($dateRecords, 'vaccine'),
                    'notes' => $this->mergedText($dateRecords, 'notes'),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }

    public function layer(array $filters): array
    {
        $records = $this->records($filters, 'layer', true);
        $populationByCoopDate = $this->populationByCoopDate($records, $filters);

        return $records
            ->groupBy(fn ($record) => $record->date->toDateString())
            ->map(function (Collection $dateRecords, string $date) use ($populationByCoopDate) {
                $population = $this->populationForDate($dateRecords, $date, $populationByCoopDate);
                $eggRows = $dateRecords->pluck('dailyEggProduction')->filter();
                $normalEggCount = (int) $eggRows->sum('marketable_eggs_count');
                $normalEggWeightKg = (float) $eggRows->sum('marketable_eggs_weight_kg');
                $sortedEggCount = (int) $eggRows->sum('sorted_eggs_count');
                $sortedEggWeightKg = (float) $eggRows->sum('sorted_eggs_weight_kg');
                $brokenEggCount = (int) $eggRows->sum('broken_eggs_count');
                $brokenEggWeightKg = (float) $eggRows->sum('broken_eggs_weight_kg');
                $spoiledEggCount = (int) $eggRows->sum('spoiled_eggs_count');
                $spoiledEggWeightKg = (float) $eggRows->sum('spoiled_eggs_weight_kg');
                $totalEggs = $normalEggCount + $sortedEggCount + $brokenEggCount + $spoiledEggCount;
                $totalEggWeightKg = $normalEggWeightKg + $sortedEggWeightKg + $brokenEggWeightKg + $spoiledEggWeightKg;
                $feedKg = (float) $dateRecords->sum('feed_kg');
                $waterL = (float) $dateRecords->sum('water_l');

                return [
                    'date' => $date,
                    'age_week' => $this->sameAge($dateRecords, $date, 'week'),
                    'age_day' => $this->sameAge($dateRecords, $date, 'day'),
                    'population' => $population,
                    'mortality' => (int) $dateRecords->sum('mortality_count'),
                    'current_population' => $population,
                    'feed_total_kg' => round($feedKg, 2),
                    'feed_gram_per_bird' => $this->ratio($feedKg * 1000, $population),
                    'water_total_l' => round($waterL, 2),
                    'water_ml_per_bird' => $this->ratio($waterL * 1000, $population),
                    'normal_egg_count' => $normalEggCount,
                    'normal_egg_weight_kg' => round($normalEggWeightKg, 3),
                    'sorted_egg_count' => $sortedEggCount,
                    'sorted_egg_weight_kg' => round($sortedEggWeightKg, 3),
                    'broken_egg_count' => $brokenEggCount,
                    'broken_egg_weight_kg' => round($brokenEggWeightKg, 3),
                    'spoiled_egg_count' => $spoiledEggCount,
                    'spoiled_egg_weight_kg' => round($spoiledEggWeightKg, 3),
                    'avg_egg_weight_g' => $this->ratio($totalEggWeightKg * 1000, $totalEggs),
                    'egg_weight_per_1000_birds' => $this->ratio($totalEggWeightKg * 1000, $population),
                    'hen_day_production' => $this->percentage($totalEggs, $population),
                    'fcr' => $this->ratio($feedKg, $totalEggWeightKg),
                    'feed_name' => $this->mergedText($dateRecords, 'feed_name'),
                    'medicine_vitamin' => $this->mergedText($dateRecords, 'medicine_vitamin'),
                    'vaccine' => $this->mergedText($dateRecords, 'vaccine'),
                    'notes' => $this->mergedText($dateRecords, 'notes'),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }

    public function unavailableFields(string $type): array
    {
        return match ($type) {
            'grower' => [
                'feed_standard_gram_per_bird' => 'Tidak ada kolom standar konsumsi pakan pada migration/database.',
                'standard_body_weight_g' => 'Tidak ada kolom standar body weight pada migration/database.',
                'standard_uniformity_percent' => 'Tidak ada kolom standar uniformity pada migration/database.',
            ],
            default => [],
        };
    }

    private function records(array $filters, string $farmType, bool $withEggProduction): Collection
    {
        return DailyFlockRecord::query()
            ->select([
                'id',
                'coop_id',
                'date',
                'feed_kg',
                'water_l',
                'avg_body_weight_kg',
                'mortality_count',
                'culling_count',
                'temperature_c',
                'uniformity_percent',
                'feed_name',
                'medicine_vitamin',
                'vaccine',
                'notes',
            ])
            ->with([
                'coop:id,flock_id,initial_population',
                'coop.flock:id,farm_id,age_in_day,start_date',
                ...($withEggProduction ? [
                    'dailyEggProduction:id,daily_flock_record_id,marketable_eggs_count,marketable_eggs_weight_kg,sorted_eggs_count,sorted_eggs_weight_kg,spoiled_eggs_count,spoiled_eggs_weight_kg,broken_eggs_count,broken_eggs_weight_kg',
                ] : []),
            ])
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->whereHas('coop.flock.farm', function ($query) use ($filters, $farmType) {
                $query
                    ->where('id', $filters['farm_id'])
                    ->where('type', $farmType);
            })
            ->when($filters['flock_id'], function ($query, $flockId) {
                $query->whereHas('coop', fn ($coopQuery) => $coopQuery->where('flock_id', $flockId));
            })
            ->when($filters['coop_id'], fn ($query, $coopId) => $query->where('coop_id', $coopId))
            ->orderBy('date')
            ->get();
    }

    private function populationByCoopDate(Collection $records, array $filters): Collection
    {
        $coopIds = $records->pluck('coop_id')->unique()->values();
        $initialPopulationByCoop = $records
            ->pluck('coop')
            ->filter()
            ->keyBy('id')
            ->map(fn ($coop) => (int) $coop->initial_population);

        if ($coopIds->isEmpty()) {
            return collect();
        }

        $history = DailyFlockRecord::query()
            ->select(['id', 'coop_id', 'date', 'mortality_count', 'culling_count'])
            ->whereIn('coop_id', $coopIds)
            ->whereDate('date', '<=', $filters['end_date'])
            ->orderBy('date')
            ->get()
            ->groupBy('coop_id');

        return $history->map(function (Collection $coopRecords, int|string $coopId) use ($initialPopulationByCoop) {
            $runningMortality = 0;
            $runningCulling = 0;
            $initialPopulation = (int) ($initialPopulationByCoop[$coopId] ?? 0);

            return $coopRecords
                ->mapWithKeys(function ($record) use (&$runningMortality, &$runningCulling, $initialPopulation) {
                    $runningMortality += (int) $record->mortality_count;
                    $runningCulling += (int) $record->culling_count;

                    return [
                        $record->date->toDateString() => max(0, $initialPopulation - $runningMortality - $runningCulling),
                    ];
                });
        });
    }

    private function populationForDate(Collection $records, string $date, Collection $populationByCoopDate): int
    {
        return (int) $records
            ->pluck('coop_id')
            ->unique()
            ->sum(fn ($coopId) => $this->populationForCoopDate($coopId, $date, $populationByCoopDate));
    }

    private function populationForCoopDate(int|string $coopId, string $date, Collection $populationByCoopDate): int
    {
        $snapshots = $populationByCoopDate->get($coopId, collect());

        if ($snapshots->has($date)) {
            return (int) $snapshots->get($date);
        }

        return (int) $snapshots
            ->filter(fn (int $population, string $snapshotDate) => $snapshotDate <= $date)
            ->last(default: 0);
    }

    private function weightedBodyWeightKg(Collection $records, string $date, Collection $populationByCoopDate): float
    {
        $weightedTotal = 0;
        $populationTotal = 0;

        foreach ($records as $record) {
            $coopPopulation = $this->populationForCoopDate($record->coop_id, $date, $populationByCoopDate);

            if ($coopPopulation <= 0) {
                continue;
            }

            $weightedTotal += (float) $record->avg_body_weight_kg * $coopPopulation;
            $populationTotal += $coopPopulation;
        }

        if ($populationTotal <= 0) {
            return (float) $records->avg('avg_body_weight_kg');
        }

        return round($weightedTotal / $populationTotal, 4);
    }

    private function sameAge(Collection $records, string $date, string $unit): ?int
    {
        $ages = $records
            ->map(function ($record) use ($date) {
                $flock = $record->coop->flock;
                $recordDate = $this->date($date);
                $startDate = $this->date($flock->start_date);

                return (int) $flock->age_in_day + $startDate->diffInDays($recordDate, false);
            })
            ->unique()
            ->values();

        if ($ages->count() !== 1) {
            return null;
        }

        $ageDay = (int) $ages->first();

        return $unit === 'week' ? intdiv($ageDay, 7) : $ageDay;
    }

    private function date(string|CarbonInterface $date): CarbonInterface
    {
        return $date instanceof CarbonInterface ? $date : Carbon::parse($date);
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $this->ratio($value * 100, $total);
    }

    private function ratio(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round($value / $total, 2);
    }

    private function nullableAverage(Collection $records, string $field, int $precision = 2): ?float
    {
        $values = $records
            ->pluck($field)
            ->filter(fn ($val) => $val !== null && $val !== '');

        if ($values->isEmpty()) {
            return null;
        }

        return round((float) $values->avg(), $precision);
    }

    private function mergedText(Collection $records, string $field): ?string
    {
        $values = $records
            ->pluck($field)
            ->map(fn ($val) => is_string($val) ? trim($val) : '')
            ->filter(fn (string $val) => $val !== '')
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        return $values->implode(', ');
    }
}
