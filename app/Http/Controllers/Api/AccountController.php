<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    use ApiResponse;

    /**
     * Register an optional account and issue a bearer token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => explode('@', $request->validated('email'))[0],
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        return $this->successResponse([
            'token' => $user->createToken('account')->plainTextToken,
        ], 'Welcome — your account is ready', 201);
    }

    /**
     * Log in and issue a bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        return $this->successResponse([
            'token' => $user->createToken('account')->plainTextToken,
        ], 'Welcome back');
    }

    /**
     * The account's orders, matched by email — including guest orders
     * placed before the account existed.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with('items.menuItem')
            ->where('email', $request->user()->email)
            ->orderByDesc('placed_at')
            ->get();

        return $this->successResponse(OrderResource::collection($orders), 'Here are your orders');
    }

    /**
     * Prefill details from the account's most recent order.
     */
    public function prefill(Request $request): JsonResponse
    {
        $latest = Order::query()
            ->where('email', $request->user()->email)
            ->orderByDesc('placed_at')
            ->first();

        return $this->successResponse([
            'name' => $latest?->name,
            'email' => $request->user()->email,
            'phone' => $latest?->phone,
            'address' => $latest?->address,
            'suburb' => $latest?->suburb,
        ], 'Here are your details');
    }
}
