<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmController extends Controller
{
    public function index(Request $request)
    {
        $query = Farm::query()
            ->withCount(['flocks', 'users'])
            ->forUser(Auth::user());

        $query = $this->applyFilters($query, $request);

        $farms = $query->paginate(15);
        $farms->getCollection()->transform(function (Farm $farm) {
            $farm->can_delete = $farm->flocks_count === 0 && $farm->users_count === 0;

            return $farm;
        });

        return response()->json($farms);
    }

    public function store(StoreFarmRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'owner_id' => $request->user()->id,
        ]);

        $farm = Farm::create($data);

        return response()->json(['message' => 'Farm created', 'data' => $farm], 201);
    }

    public function show(Farm $farm): JsonResponse
    {
        $user = Auth::user();
        abort_if(!Farm::forUser($user)->where('id', $farm->id)->exists(), 403);

        // $farm->load(['coops.activeCycle', 'feedInventory']);
        return response()->json($farm);
    }

    public function update(StoreFarmRequest $request, Farm $farm): JsonResponse
    {
        $farm->update($request->validated());
        return response()->json(['message' => 'Farm updated', 'data' => $farm]);
    }

    public function destroy(Farm $farm): JsonResponse
    {
        $user = Auth::user();

        abort_if(!$user->isSuperAdmin() || $farm->owner_id !== $user->id, 403);

        if ($farm->flocks()->exists()) {
            return response()->json([
                'message' => 'Farm tidak dapat dihapus karena sudah memiliki flock.',
            ], 422);
        }

        if (User::where('farm_id', $farm->id)->exists()) {
            return response()->json([
                'message' => 'Farm tidak dapat dihapus karena masih digunakan oleh user.',
            ], 422);
        }

        $farm->delete();
        return response()->json(['message' => 'Farm deleted'], 200);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query;
    }
}
