<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminCustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customers = QueryBuilder::for(Customer::class)
            ->allowedFilters('name', 'email', 'suburb')
            ->allowedSorts('name')
            ->defaultSort('name')
            ->withCount('orders')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->paginatedResponse(AdminCustomerResource::collection($customers), 'Customers');
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['orders' => fn ($query) => $query->latest('placed_at'), 'orders.payments']);

        return $this->successResponse(new AdminCustomerResource($customer), 'Customer');
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'suburb' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $customer->update($validated);

        return $this->successResponse(new AdminCustomerResource($customer->refresh()), 'Customer updated');
    }
}
