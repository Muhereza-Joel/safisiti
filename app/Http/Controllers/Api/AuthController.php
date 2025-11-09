<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate request fields
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if the user exists
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'errors' => [
                    'email' => ['No account found with this email.'],
                ],
            ], 422);
        }

        // Check if the password is correct
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'errors' => [
                    'password' => ['Incorrect password.'],
                ],
            ], 422);
        }

        // At this point the credentials are valid; get the authenticated user instance
        /** @var \App\Models\User $user */
        $user = $request->user()->load('roles.permissions');

        // Gate by role
        $allowedRoles = [
            'Organisation Administrator',
            'Service Provider',
            'Collection Agent',
            'Health Inspector',
            'Site Manager',
            'Data Clerk',
        ];

        if (! $user->hasAnyRole($allowedRoles)) {
            Auth::logout(); // optional: ensure session guard cleared

            return response()->json([
                'errors' => [
                    'role' => ['Your account is not authorized to access this application.'],
                ],
            ], 403);
        }

        // Generate token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function dataGridLogin(Request $request)
    {
        // Validate request input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check user existence
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'errors' => [
                    'email' => ['No account found with this email.'],
                ],
            ], 422);
        }

        // Verify password
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'errors' => [
                    'password' => ['Incorrect password.'],
                ],
            ], 422);
        }

        // Load roles and permissions
        $user = $request->user()->load('roles.permissions');

        // ✅ Only allow root and System Administrator
        $allowedRoles = ['root', 'System Administrator'];

        if (! $user->hasAnyRole($allowedRoles)) {
            Auth::logout();

            return response()->json([
                'errors' => [
                    'role' => ['Your account is not authorized to access the Data Grid.'],
                ],
            ], 403);
        }

        // Generate Sanctum token
        $token = $user->createToken('DATA_GRID_TOKEN')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function dataGridUser(Request $request)
    {
        // Ensure user is authenticated
        $user = $request->user()->load('roles.permissions');

        // Check allowed access roles
        $allowedRoles = ['root', 'System Administrator'];

        if (! $user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'errors' => [
                    'role' => ['You are not authorized to access the Data Grid.'],
                ],
            ], 403);
        }

        return response()->json($user, 200);
    }




    // Logout method
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }


    public function getRoles()
    {
        return response()->json([
            "roles" => Role::whereNotIn('name', ['root', 'admin', 'executive member'])->select('id', 'name')->get()
        ]);
    }

    public function getTeamMembers()
    {
        $executiveMembers = User::whereHas('roles', function ($query) {
            $query->where('name', 'executive member');
        })->with('roles')->get();

        return response()->json($executiveMembers, 200);
    }
}
