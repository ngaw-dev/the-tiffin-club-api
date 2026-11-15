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
            'email' => strtolower($request->validated('email')),
            'password' => $request->validated('password'),
        ]);

        return $this->successResponse(
            $this->tokenPayload($user),
            'Welcome — your account is ready',
            201,
        );
    }

    /**
     * Log in and issue a bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->validated('email')))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        return $this->successResponse($this->tokenPayload($user), 'Welcome back');
    }

    /**
     * The account's orders — via the Customer whose email matches the
     * account email, including guest orders placed before the account
     * existed.
     */
    public function orders(Request $request): JsonResponse
    {
        return $this->successResponse(
            OrderResource::collection($this->ordersFor($request->user())->with('items.menuItem', 'customer')->get()),
            'Here are your orders',
        );
    }

    /**
     * Prefill details from the account's most recent order.
     */
    public function prefill(Request $request): JsonResponse
    {
        $latest = $this->ordersFor($request->user())->first();

        return $this->successResponse([
            'name' => $latest?->customer?->name,
            'email' => $request->user()->email,
            'phone' => $latest?->customer?->phone,
            'address' => $latest?->customer?->address,
            'suburb' => $latest?->customer?->suburb,
        ], 'Here are your details');
    }

    /**
     * @return array<string, string>
     */
    private function tokenPayload(User $user): array
    {
        return ['token' => $user->createToken('account')->plainTextToken];
    }

    /**
     * Base query for the account's orders, newest first (id as a
     * stable tie-breaker), resolved through the email-matched
     * customer.
     */
    private function ordersFor(User $user)
    {
        return Order::query()
            ->whereHas('customer', fn ($q) => $q->where('email', $user->email))
            ->orderByDesc('placed_at')
            ->orderByDesc('id');
    }
}
