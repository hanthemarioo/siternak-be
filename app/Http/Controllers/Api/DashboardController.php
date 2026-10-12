<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Flock;
use App\Models\Coop;
use App\Models\DailyFlockRecord;
use App\Models\DailyEggProduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $filters = $this->validatedFilters($request);

        $farmQuery = $this->scopedFarmQuery($user, $filters);
        $flockQuery = $this->scopedFlockQuery($user, $filters);
        $coopQuery = $this->scopedCoopQuery($user, $filters);

        $todayRecords = $this->scopedRecordQuery($user, $filters, false)
            ->whereDate('date', today());

        $periodRecords = $this->scopedRecordQuery($user, $filters);
        $populationRecords = $this->scopedRecordQuery($user, $filters, false)
            ->whereDate('date', '<=', today());

        $eggToday = $this->scopedEggQuery($user, $filters, false)
            ->whereDate('daily_flock_records.date', today())
            ->selectRaw($this->eggSelectRaw())
            ->first();

        $eggPeriod = $this->scopedEggQuery($user, $filters)
            ->selectRaw($this->eggSelectRaw())
            ->first();

        $activeCoopQuery = (clone $coopQuery)
            ->whereHas('flock', fn ($query) => $query->where('status', 'active'));

        $initialPopulation = (int) $activeCoopQuery->sum('initial_population');
        $totalMortality = (int) (clone $populationRecords)->sum('mortality_count');
        $totalCulling = (int) (clone $populationRecords)->sum('culling_count');
        $currentPopulation = max(0, $initialPopulation - $totalMortality - $totalCulling);
        $periodDays = max(1, (clone $periodRecords)->distinct()->count('date'));

        $layerFilters = [...$filters, 'type' => 'layer'];
        $layerInitialPopulation = (int) $this->scopedCoopQuery($user, $layerFilters)
            ->whereHas('flock', fn ($query) => $query->where('status', 'active'))
            ->sum('initial_population');
        $layerPopulationRecords = $this->scopedRecordQuery($user, $layerFilters, false)
            ->whereDate('date', '<=', today());
        $layerCurrentPopulation = max(
            0,
            $layerInitialPopulation
                - (int) (clone $layerPopulationRecords)->sum('mortality_count')
                - (int) (clone $layerPopulationRecords)->sum('culling_count')
        );

        return response()->json([
            'filters' => $filters,
            'total_farms' => (clone $farmQuery)->count(),
            'total_flocks' => (clone $flockQuery)->where('status', 'active')->count(),
            'total_coops' => (clone $coopQuery)->count(),
            'initial_population' => $initialPopulation,
            'current_population' => $currentPopulation,
            'mortality_rate_percent' => $this->percentage($totalMortality, $initialPopulation),

            'total_feed_today' => (float) (clone $todayRecords)->sum('feed_kg'),
            'total_water_today' => (float) (clone $todayRecords)->sum('water_l'),
            'total_mortality_today' => (int) (clone $todayRecords)->sum('mortality_count'),
            'total_culling_today' => (int) (clone $todayRecords)->sum('culling_count'),
            'total_eggs_today' => (int) ($eggToday->total_eggs ?? 0),
            'marketable_eggs_today' => (int) ($eggToday->marketable_eggs ?? 0),
            'sorted_eggs_today' => (int) ($eggToday->sorted_eggs ?? 0),
            'spoiled_eggs_today' => (int) ($eggToday->spoiled_eggs ?? 0),
            'broken_eggs_today' => (int) ($eggToday->broken_eggs ?? 0),

            'period' => [
                'total_feed' => (float) (clone $periodRecords)->sum('feed_kg'),
                'total_water' => (float) (clone $periodRecords)->sum('water_l'),
                'total_mortality' => (int) (clone $periodRecords)->sum('mortality_count'),
                'total_culling' => (int) (clone $periodRecords)->sum('culling_count'),
                'avg_body_weight_kg' => round((float) (clone $periodRecords)->avg('avg_body_weight_kg'), 2),
                'total_eggs' => (int) ($eggPeriod->total_eggs ?? 0),
                'marketable_eggs' => (int) ($eggPeriod->marketable_eggs ?? 0),
                'sorted_eggs' => (int) ($eggPeriod->sorted_eggs ?? 0),
                'spoiled_eggs' => (int) ($eggPeriod->spoiled_eggs ?? 0),
                'broken_eggs' => (int) ($eggPeriod->broken_eggs ?? 0),
                'egg_production_rate_percent' => $this->percentage(
                    (int) ($eggPeriod->total_eggs ?? 0),
                    $layerCurrentPopulation * $periodDays
                ),
            ],
        ]);
    }

    public function feedChart(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $data = $this->scopedRecordQuery($request->user(), $filters)
            ->select(
                'date',
                DB::raw('SUM(feed_kg) as total_feed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function mortalityChart(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $data = $this->scopedRecordQuery($request->user(), $filters)
            ->select(
                'date',
                DB::raw('SUM(mortality_count) as total_mortality'),
                DB::raw('SUM(culling_count) as total_culling')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function eggChart(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $data = $this->scopedEggQuery($request->user(), $filters)
            ->select(
                'daily_flock_records.date',
                DB::raw('SUM(marketable_eggs_count + sorted_eggs_count + spoiled_eggs_count + broken_eggs_count) as total_eggs'),
                DB::raw('SUM(marketable_eggs_count) as marketable_eggs'),
                DB::raw('SUM(sorted_eggs_count) as sorted_eggs'),
                DB::raw('SUM(spoiled_eggs_count) as spoiled_eggs'),
                DB::raw('SUM(broken_eggs_count) as broken_eggs')
            )
            ->groupBy('daily_flock_records.date')
            ->orderBy('daily_flock_records.date')
            ->get();

        return response()->json($data);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'type' => ['nullable', 'in:layer,grower'],
            'farm_id' => ['nullable', 'integer', 'exists:farms,id'],
            'flock_id' => ['nullable', 'integer', 'exists:flocks,id'],
            'coop_id' => ['nullable', 'integer', 'exists:coops,id'],
        ]);
    }

    private function scopedFarmQuery($user, array $filters)
    {
        return Farm::query()
            ->when($user->isSuperAdmin(), fn ($query) => $query->where('owner_id', $user->id))
            ->when(!$user->isSuperAdmin(), fn ($query) => $query->where('id', $user->farm_id))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['farm_id'] ?? null, fn ($query, $farmId) => $query->where('id', $farmId))
            ->when($filters['flock_id'] ?? null, function ($query, $flockId) {
                $query->whereHas('flocks', fn ($flockQuery) => $flockQuery->where('id', $flockId));
            })
            ->when($filters['coop_id'] ?? null, function ($query, $coopId) {
                $query->whereHas('flocks.coops', fn ($coopQuery) => $coopQuery->where('id', $coopId));
            });
    }

    private function scopedFlockQuery($user, array $filters)
    {
        return Flock::query()
            ->whereHas('farm', function ($query) use ($user, $filters) {
                $this->applyFarmScope($query, $user, $filters);
            })
            ->when($filters['flock_id'] ?? null, fn ($query, $flockId) => $query->where('id', $flockId))
            ->when($filters['coop_id'] ?? null, function ($query, $coopId) {
                $query->whereHas('coops', fn ($coopQuery) => $coopQuery->where('id', $coopId));
            });
    }

    private function scopedCoopQuery($user, array $filters)
    {
        return Coop::query()
            ->whereHas('flock.farm', function ($query) use ($user, $filters) {
                $this->applyFarmScope($query, $user, $filters);
            })
            ->when($filters['flock_id'] ?? null, function ($query, $flockId) {
                $query->where('flock_id', $flockId);
            })
            ->when($filters['coop_id'] ?? null, fn ($query, $coopId) => $query->where('id', $coopId));
    }

    private function scopedRecordQuery($user, array $filters, bool $withDateFilters = true)
    {
        return DailyFlockRecord::query()
            ->whereHas('coop.flock.farm', function ($query) use ($user, $filters) {
                $this->applyFarmScope($query, $user, $filters);
            })
            ->when($filters['flock_id'] ?? null, function ($query, $flockId) {
                $query->whereHas('coop', fn ($coopQuery) => $coopQuery->where('flock_id', $flockId));
            })
            ->when($filters['coop_id'] ?? null, fn ($query, $coopId) => $query->where('coop_id', $coopId))
            ->when($withDateFilters && ($filters['start_date'] ?? null), function ($query) use ($filters) {
                $query->whereDate('date', '>=', $filters['start_date']);
            })
            ->when($withDateFilters && ($filters['end_date'] ?? null), function ($query) use ($filters) {
                $query->whereDate('date', '<=', $filters['end_date']);
            });
    }

    private function scopedEggQuery($user, array $filters, bool $withDateFilters = true)
    {
        return DailyEggProduction::query()
            ->join(
                'daily_flock_records',
                'daily_egg_productions.daily_flock_record_id',
                '=',
                'daily_flock_records.id'
            )
            ->join('coops', 'daily_flock_records.coop_id', '=', 'coops.id')
            ->join('flocks', 'coops.flock_id', '=', 'flocks.id')
            ->join('farms', 'flocks.farm_id', '=', 'farms.id')
            ->when($user->isSuperAdmin(), fn ($query) => $query->where('farms.owner_id', $user->id))
            ->when(!$user->isSuperAdmin(), fn ($query) => $query->where('farms.id', $user->farm_id))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('farms.type', $type))
            ->when($filters['farm_id'] ?? null, fn ($query, $farmId) => $query->where('farms.id', $farmId))
            ->when($filters['flock_id'] ?? null, fn ($query, $flockId) => $query->where('flocks.id', $flockId))
            ->when($filters['coop_id'] ?? null, fn ($query, $coopId) => $query->where('coops.id', $coopId))
            ->when($withDateFilters && ($filters['start_date'] ?? null), function ($query) use ($filters) {
                $query->whereDate('daily_flock_records.date', '>=', $filters['start_date']);
            })
            ->when($withDateFilters && ($filters['end_date'] ?? null), function ($query) use ($filters) {
                $query->whereDate('daily_flock_records.date', '<=', $filters['end_date']);
            });
    }

    private function applyFarmScope($query, $user, array $filters): void
    {
        if ($user->isSuperAdmin()) {
            $query->where('owner_id', $user->id);
        } else {
            $query->where('id', $user->farm_id);
        }

        $query
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['farm_id'] ?? null, fn ($q, $farmId) => $q->where('id', $farmId));
    }

    private function eggSelectRaw(): string
    {
        return '
            SUM(marketable_eggs_count + sorted_eggs_count + spoiled_eggs_count + broken_eggs_count) as total_eggs,
            SUM(marketable_eggs_count) as marketable_eggs,
            SUM(sorted_eggs_count) as sorted_eggs,
            SUM(spoiled_eggs_count) as spoiled_eggs,
            SUM(broken_eggs_count) as broken_eggs
        ';
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
