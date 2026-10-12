<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLayerPhaseRequest;
use App\Models\DailyFlockRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LayerPhaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = DailyFlockRecord::query()
            ->with([
                'coop.flock.farm',
                'creator:id,name,role',
                'dailyEggProduction:id,daily_flock_record_id,marketable_eggs_count,marketable_eggs_weight_kg,sorted_eggs_count,sorted_eggs_weight_kg,spoiled_eggs_count,spoiled_eggs_weight_kg,broken_eggs_count,broken_eggs_weight_kg'
            ]);

        // APPLY ROLE FILTER
        $query = $this->applyRoleAndFarmTypeFilter($query, $user);

        // OPTIONAL FILTER (biar future proof)
        $query = $this->applyFilters($query, $request);

        // PAGINATION
        $records = $query
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        return response()->json($records);
    }

    public function store(StoreLayerPhaseRequest $request)
    {
        $user = Auth::user();

        $data = $request->validated();
        $populationData = Arr::except($data, [
            'marketable_eggs_count',
            'marketable_eggs_weight_kg',
            'sorted_eggs_count',
            'sorted_eggs_weight_kg',
            'spoiled_eggs_count',
            'spoiled_eggs_weight_kg',
            'broken_eggs_count',
            'broken_eggs_weight_kg',
        ]);
        $eggProductionData = Arr::except($data, [
            'coop_id',
            'feed_kg',
            'water_l',
            'avg_body_weight_kg',
            'mortality_count',
            'culling_count',
        ]);

        // VALIDASI: pastikan user boleh akses coop tsb
        $this->authorizeCoop($data['coop_id'], $user);

        $exists = DailyFlockRecord::where('coop_id', $data['coop_id'])
            ->whereDate('date', (now()->toDateString()))
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Data untuk kandang ini hari ini sudah ada'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Simpan data populasi (sama seperti grower)
            $record = DailyFlockRecord::create([
                ...$populationData,
                'created_by' => $user->id,
                'date' => (now()->toDateString()),
            ]);

            // 2. Simpan data telur (KHUSUS LAYER)
            $record->dailyEggProduction()->create($eggProductionData);

            DB::commit();

            return response()->json([
                'message' => 'Data layer berhasil disimpan',
                'data' => $record
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(DailyFlockRecord $layerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($layerPhase, $user);

        return response()->json(
            $layerPhase->load('coop.flock.farm')
        );
    }

    public function update(StoreLayerPhaseRequest $request, DailyFlockRecord $layerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($layerPhase, $user);

        $data = $request->validated();
        $populationData = Arr::except($data, [
            'marketable_eggs_count',
            'marketable_eggs_weight_kg',
            'sorted_eggs_count',
            'sorted_eggs_weight_kg',
            'spoiled_eggs_count',
            'spoiled_eggs_weight_kg',
            'broken_eggs_count',
            'broken_eggs_weight_kg',
        ]);
        $eggProductionData = Arr::except($data, [
            'coop_id',
            'feed_kg',
            'water_l',
            'avg_body_weight_kg',
            'mortality_count',
            'culling_count',
        ]);

        $this->authorizeCoop($data['coop_id'], $user);

        DB::beginTransaction();

        try {
            $layerPhase->update($populationData);

            // 2. Simpan data telur (KHUSUS LAYER)
            $layerPhase->dailyEggProduction()->update($eggProductionData);

            DB::commit();

            return response()->json([
                'message' => 'Data layer berhasil disimpan',
                'data' => $layerPhase
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(DailyFlockRecord $layerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($layerPhase, $user);

        if ($user->role === 'user_input') {
            return abort(403);
        }

        $layerPhase->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }

    // =========================================================
    // CORE LOGIC (INI YANG PALING PENTING)
    // =========================================================

    private function applyRoleAndFarmTypeFilter($query, $user)
    {
        return $query->whereHas('coop.flock.farm', function ($q) use ($user) {

            // SUPER ADMIN (OWNER)
            if ($user->isSuperAdmin()) {
                $q->where('owner_id', $user->id);
            }
            // ADMIN / USER INPUT
            else {
                $q->where('id', $user->farm_id);
            }

            $q->where('type', 'layer');
        });
    }

    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->start_date, function ($q) use ($request) {
                $q->whereDate('date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                $q->whereDate('date', '<=', $request->end_date);
            })
            ->when($request->coop_id, function ($q) use ($request) {
                $q->where('coop_id', $request->coop_id);
            })
            ->when($request->created_by, function ($q) use ($request) {
                $q->where('created_by', $request->created_by);
            });
    }

    // =========================================================
    // AUTHORIZATION
    // =========================================================

    private function authorizeRecord(DailyFlockRecord $record, $user)
    {
        $farm = $record->coop->flock->farm;

        if ($user->isSuperAdmin()) {
            abort_if($farm->owner_id !== $user->id, 403);
        } else {
            abort_if($farm->id !== $user->farm_id, 403);
        }
    }

    private function authorizeCoop($coopId, $user)
    {
        $record = DailyFlockRecord::with('coop.flock.farm')
            ->where('coop_id', $coopId)
            ->first();

        if (!$record) return;

        $this->authorizeRecord($record, $user);
    }
}
