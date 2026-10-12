<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $auth = Auth::user();

        abort_if(!$auth->isSuperAdmin(), 403, 'Unauthorized');

        $query = User::query()
            ->with('farm')
            ->whereIn('role', ['admin', 'user_input'])
            ->where('created_by', $auth->id)
            ->latest();

        $query = $this->applyFilters($query, $request);

        return response()->json($query->paginate(15));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $auth = Auth::user();

        $data = $request->validated();
        $data['created_by'] = $auth->id;

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorizeManagedUser($user);

        return response()->json($user->load('farm'));
    }

    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        $this->authorizeManagedUser($user);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->fresh('farm')
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizeManagedUser($user);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function authorizeManagedUser(User $user): void
    {
        $auth = Auth::user();

        abort_if(!$auth->isSuperAdmin(), 403, 'Unauthorized');

        abort_if(
            $auth->id !== $user->created_by || !in_array($user->role, ['admin', 'user_input']),
            403,
            'Forbidden'
        );
    }
}
