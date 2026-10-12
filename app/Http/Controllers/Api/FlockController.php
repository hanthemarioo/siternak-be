<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseFlockRequest;
use App\Http\Requests\StoreFlockRequest;
use App\Models\Coop;
use App\Models\Flock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Flock::with(['farm', 'coops'])
            ->withCount([
                'coops as production_coops_count' => function ($query) {
                    $query->whereHas('dailyFlockRecord');
                },
            ])
            ->forUser(Auth::user());

        $query = $this->applyFilters($query, $request);

        $flocks = $query->paginate(15);
        $flocks->getCollection()->transform(function (Flock $flock) {
            $flock->can_delete = !$this->hasProductionRecords($flock);

            return $flock;
        });

        return response()->json($flocks);
    }

    public function store(StoreFlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $flock = $this->addFlock($data);

        return response()->json(['message' => 'Flock created', 'data' => $flock], 201);
    }

    public function update(StoreFlockRequest $request, Flock $flock): JsonResponse
    {
        $data = $request->validated();

        $flock->update($data);

        return response()->json(['message' => 'Flock updated', 'data' => $flock], 201);
    }

    public function destroy(Flock $flock): JsonResponse
    {
        $this->authorizeManageFlock($flock);

        if ($this->hasProductionRecords($flock)) {
            return response()->json([
                'message' => 'Flock tidak dapat dihapus karena sudah memiliki data produksi. Gunakan fitur Close Flock untuk menutup siklus.',
            ], 422);
        }

        $flock->delete();
        return response()->json(['message' => 'Flock deleted'], 200);
    }

    public function close(CloseFlockRequest $request, Flock $flock): JsonResponse
    {
        $this->authorizeManageFlock($flock);

        if ($flock->status === 'closed') {
            return response()->json([
                'message' => 'Flock sudah ditutup.',
            ], 422);
        }

        if (!$this->hasProductionRecords($flock)) {
            return response()->json([
                'message' => 'Flock belum memiliki data produksi. Hapus flock jika ini adalah data salah input.',
            ], 422);
        }

        $flock->update([
            'status' => 'closed',
            'closed_at' => $request->validated('closed_at'),
            'closing_reason' => $request->validated('closing_reason'),
            'closing_note' => $request->validated('closing_note'),
        ]);

        return response()->json([
            'message' => 'Flock berhasil ditutup.',
            'data' => $flock->load(['farm', 'coops']),
        ]);
    }

    public function getCoops(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flock_id' => ['required', 'exists:flocks,id'],
        ]);

        $flock = Flock::forUser(Auth::user())->findOrFail($validated['flock_id']);

        $coops = Coop::query()
            ->where('flock_id', $flock->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $coops
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function authorizeManageFlock(Flock $flock): void
    {
        $user = Auth::user();

        abort_if(!$user || $user->isUserInput(), 403);

        $flock->loadMissing('farm');

        if ($user->isSuperAdmin()) {
            abort_if($flock->farm->owner_id !== $user->id, 403);
            return;
        }

        abort_if($flock->farm_id !== $user->farm_id, 403);
    }

    private function hasProductionRecords(Flock $flock): bool
    {
        if (isset($flock->production_coops_count)) {
            return (int) $flock->production_coops_count > 0;
        }

        return $flock->coops()->whereHas('dailyFlockRecord')->exists();
    }

    private function addFlock(array $data): Flock
    {
        return DB::transaction(function () use ($data) {
            $coops = $data['coops'] ?? [];
            unset($data['coops']);

            $flock = Flock::create($data);

            $flock->coops()->createMany($coops);

            return $flock;
        });
    }
}
