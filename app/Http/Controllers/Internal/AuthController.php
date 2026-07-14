<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function verifyCredentials(Request $request)
    {
        $data = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ])->validate();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        return response()->json(['user' => $user->load('roles')]);
    }

    public function issueToken(Request $request)
    {
        $data = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'sometimes|string',
        ])->validate();

        $user = User::findOrFail($data['user_id']);
        $token = $user->createToken($data['name'] ?? 'backend-session');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'user' => $user->load('roles'),
        ]);
    }

    public function introspectToken(Request $request)
    {
        $data = Validator::make($request->all(), [
            'token' => 'required|string',
        ])->validate();

        $accessToken = PersonalAccessToken::findToken($data['token']);

        if (! $accessToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $user = $accessToken->tokenable;

        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        return response()->json([
            'token_id' => $accessToken->id,
            'user' => $user->load('roles'),
        ]);
    }

    public function revokeToken(int $tokenId)
    {
        PersonalAccessToken::whereKey($tokenId)->delete();

        return response()->json(null, 204);
    }
}
