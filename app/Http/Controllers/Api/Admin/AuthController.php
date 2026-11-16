<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()->where('email', strtolower($validated['email']))->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse(
                errors: 'These credentials do not match our records.',
                message: 'Login failed',
                statusCode: 422,
            );
        }

        if (! in_array(strtolower($user->email), config('staff.emails', []), true)) {
            return $this->errorResponse(errors: 'Not an admin account', message: 'Forbidden', statusCode: 403);
        }

        $token = $user->createToken('admin-spa')->plainTextToken;

        return $this->successResponse(['token' => $token, 'user' => ['name' => $user->name, 'email' => $user->email]], 'Welcome back');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(message: 'Logged out');
    }
}
