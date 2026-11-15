<?php

// app/Http/Controllers/Api/MyAccountController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clerk-authenticated account endpoints. The customer is resolved by
 * the verified email in the Clerk JWT (see ClerkAuthentication);
 * no Clerk identifiers are stored locally.
 */
class MyAccountController extends Controller
{
    use ApiResponse;

    /**
     * The signed-in customer. Creates the row from the JWT name and
     * email on first request (auto-provisioning).
     */
    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(
            new CustomerResource($this->resolveCustomer($request)),
            'Here are your details',
        );
    }

    /**
     * Update the signed-in customer's contact/delivery details.
     */
    public function update(UpdateCustomerRequest $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request);
        $customer->fill($request->validated())->save();

        return $this->successResponse(
            new CustomerResource($customer->refresh()),
            'Details updated',
        );
    }

    private function resolveCustomer(Request $request): Customer
    {
        return $request->user() ?? $this->provisionFromClaims($request);
    }

    /**
     * First verified request for an unknown email: create the
     * customer from the JWT claims.
     */
    private function provisionFromClaims(Request $request): Customer
    {
        $claims = $request->attributes->get('clerk_claims');

        return Customer::create([
            'name' => $claims->name ?? (string) $claims->email,
            'email' => strtolower((string) $claims->email),
        ]);
    }
}
