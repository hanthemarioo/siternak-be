<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGrowerPhaseRequest;
use App\Models\DailyFlockRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GrowerPhaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = DailyFlockRecord::query()
            ->with([
                'coop.flock.farm',
                'creator:id,name,role'
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

    public function store(StoreGrowerPhaseRequest $request)
    {
        $user = Auth::user();

        $data = $request->validated();

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

        $record = DailyFlockRecord::create([
            ...$data,
            'created_by' => $user->id,
            'date' => (now()->toDateString()),
        ]);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $record
        ], 201);
    }

    public function show(DailyFlockRecord $growerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($growerPhase, $user);

        return response()->json(
            $growerPhase->load('coop.flock.farm')
        );
    }

    public function update(StoreGrowerPhaseRequest $request, DailyFlockRecord $growerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($growerPhase, $user);

        $data = $request->validated();

        $this->authorizeCoop($data['coop_id'], $user);

        $growerPhase->update($data);

        return response()->json([
            'message' => 'Data berhasil diupdate',
            'data' => $growerPhase
        ]);
    }

    public function destroy(DailyFlockRecord $growerPhase)
    {
        $user = Auth::user();

        $this->authorizeRecord($growerPhase, $user);

        if ($user->role === 'user_input') {
            return abort(403);
        }

        $growerPhase->delete();

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

            $q->where('type', 'grower');
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
