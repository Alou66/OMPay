<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageUsers', Admin::class);

        $users = User::paginate(10);
        return $this->paginatedResponse($users, $users, 'Users retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manageUsers', Admin::class);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'login' => 'required|string|unique:users',
            'password' => 'required|string|min:8',
            'telephone' => 'required|string|unique:users',
            'status' => 'in:Actif,Inactif',
            'role' => 'required|in:Admin,Client',
            'cni' => 'nullable|string',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('manageUsers', Admin::class);

        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('manageUsers', Admin::class);

        $validated = $request->validate([
            'nom' => 'string|max:255',
            'prenom' => 'string|max:255',
            'status' => 'in:Actif,Inactif',
            'role' => 'in:Admin,Client',
        ]);

        $user->update($validated);
        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('manageUsers', Admin::class);

        $user->delete();
        return $this->successResponse(null, 'User deleted successfully');
    }
}