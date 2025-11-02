<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * Get users by role with pagination and search
     */
    public function getUsersByRole(Request $request): JsonResponse
    {
        $request->validate([
            'role' => 'required|string',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $role = $request->input('role');
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 20);
        $organisationId = $request->user()->organisation_id;

        if (!Role::where('name', $role)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
                'data' => []
            ], 404);
        }

        $cacheKey = "users_org_{$organisationId}_role_{$role}_search_{$search}_perpage_{$perPage}";

        $users = Cache::remember($cacheKey, 300, function () use ($role, $search, $perPage, $organisationId) {
            return User::role($role)
                ->where('organisation_id', $organisationId)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->select(['uuid', 'name', 'email', 'created_at', 'updated_at'])
                ->orderBy('name')
                ->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    public function getCollectionAgents(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request->merge(['role' => 'Collection Agent']));
    }

    public function getServiceProviders(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request->merge(['role' => 'Service Provider']));
    }

    public function getUsersByRoles(Request $request): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $roles = $request->input('roles');
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 20);
        $organisationId = $request->user()->organisation_id;

        $existingRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();
        $nonExistingRoles = array_diff($roles, $existingRoles);

        if (!empty($nonExistingRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Some roles not found: ' . implode(', ', $nonExistingRoles),
                'data' => []
            ], 404);
        }

        $users = User::role($roles)
            ->where('organisation_id', $organisationId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select(['uuid', 'name', 'email', 'phone', 'status', 'created_at', 'updated_at'])
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    public function getUserByUuid($uuid, Request $request): JsonResponse
    {
        $organisationId = $request->user()->organisation_id;

        $user = User::where('uuid', $uuid)
            ->where('organisation_id', $organisationId)
            ->select(['uuid', 'name', 'email', 'created_at', 'updated_at'])
            ->with('roles:name,guard_name')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }
}
