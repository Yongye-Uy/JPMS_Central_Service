<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /** Module 1 function 1: Search users by name/affiliation/role. */
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('full_name % ?', [$search])
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('affiliation', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('role_name', $role));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string',
            'affiliation' => 'nullable|string',
            'country' => 'nullable|string',
            'contact_info' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*.role_name' => 'required_with:roles|string|exists:roles,role_name',
            'roles.*.journal_id' => 'nullable|integer|exists:journals,id',
        ])->validate();

        $user = User::create([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $data['full_name'],
            'affiliation' => $data['affiliation'] ?? null,
            'country' => $data['country'] ?? null,
            'contact_info' => $data['contact_info'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        foreach ($data['roles'] ?? [] as $grant) {
            $role = Role::where('role_name', $grant['role_name'])->firstOrFail();
            UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'journal_id' => $grant['journal_id'] ?? null]);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function show(int $id)
    {
        return response()->json(User::with('roles')->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = Validator::make($request->all(), [
            'full_name' => 'sometimes|string',
            'affiliation' => 'nullable|string',
            'country' => 'nullable|string',
            'contact_info' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:6',
        ])->validate();

        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->load('roles'));
    }

    /** Module 1 function 6: Assign role (admin only — enforced by Backend). */
    public function assignRole(Request $request, int $id)
    {
        $data = Validator::make($request->all(), [
            'role_name' => 'required|string|exists:roles,role_name',
            'journal_id' => 'nullable|integer|exists:journals,id',
        ])->validate();

        $role = Role::where('role_name', $data['role_name'])->firstOrFail();

        $grant = UserRole::firstOrCreate([
            'user_id' => $id,
            'role_id' => $role->id,
            'journal_id' => $data['journal_id'] ?? null,
        ]);

        return response()->json($grant, 201);
    }

    public function revokeRole(int $id, int $roleId, Request $request)
    {
        UserRole::where('user_id', $id)
            ->where('role_id', $roleId)
            ->where('journal_id', $request->query('journal_id'))
            ->delete();

        return response()->json(null, 204);
    }
}
