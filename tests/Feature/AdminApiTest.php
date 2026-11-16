<?php

use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create([
        'email' => 'staff@admin.com',
        'password' => Hash::make('password'),
    ]);
}

function adminToken(): string
{
    config()->set('staff.emails', ['staff@admin.com']);
    adminUser();

    $r = postJson('/api/v1/admin/login', [
        'email' => 'staff@admin.com',
        'password' => 'password',
    ]);

    return $r->json('data.token');
}

it('logs an allowlisted admin in and rejects outsiders', function (): void {
    config()->set('staff.emails', ['staff@admin.com']);
    adminUser();

    postJson('/api/v1/admin/login', ['email' => 'staff@admin.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('success', true);

    postJson('/api/v1/admin/login', ['email' => 'staff@admin.com', 'password' => 'wrong'])
        ->assertUnprocessable();

    config()->set('staff.emails', ['other@admin.com']);
    postJson('/api/v1/admin/login', ['email' => 'staff@admin.com', 'password' => 'password'])
        ->assertForbidden();
    config()->set('staff.emails', ['staff@admin.com']);
});

it('requires authentication for admin endpoints', function (): void {
    getJson('/api/v1/admin/orders')->assertUnauthorized();
});

it('lists and filters orders', function (): void {
    $token = adminToken();
    $menuA = Menu::factory()->create(['menu_date' => today()->subDay()]);
    $menuB = Menu::factory()->create(['menu_date' => today()->subDays(2)]);
    $order = Order::factory()->create(['status' => 'confirmed', 'menu_id' => $menuA->id]);
    Order::factory()->create(['status' => 'cancelled', 'menu_id' => $menuB->id]);

    $response = getJson('/api/v1/admin/orders?filter[status]=confirmed', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.resource.0.id', $order->id);

    expect(count($response->json('data.resource')))->toBe(1);
});

it('updates an order status and audits it', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'confirmed']);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'ready_for_pickup'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.status', 'ready_for_pickup');

    expect($order->audits()->latest('id')->first()->action)->toBe('status_changed');
});

it('rejects backward status transitions', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'completed']);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'confirmed'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect($order->fresh()->status)->toBe('completed');
});

it('rejects skip-forward status transitions', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'confirmed']);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'out_for_delivery'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect($order->fresh()->status)->toBe('confirmed');
});

it('allows cancelling from each non-terminal status', function (string $status): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => $status]);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
})->with(['confirmed', 'ready_for_pickup', 'out_for_delivery']);

it('rejects cancelling terminal orders', function (string $status): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => $status]);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable();

    expect($order->fresh()->status)->toBe($status);
})->with(['completed']);

it('treats re-sending cancelled as a no-op', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'cancelled']);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertOk();

    expect($order->fresh()->status)->toBe('cancelled');
});

it('treats re-sending the current status as a no-op without a new audit entry', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'ready_for_pickup']);
    $auditsBefore = $order->audits()->count();

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'ready_for_pickup'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.status', 'ready_for_pickup');

    expect($order->audits()->count())->toBe($auditsBefore);
});

it('rejects an invalid order status', function (): void {
    $token = adminToken();
    $order = Order::factory()->create();

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'teleported'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable();
});

it('blocks cancelling an order with recorded payments', function (): void {
    $token = adminToken();
    $order = Order::factory()->create();
    Payment::factory()->create(['order_id' => $order->id]);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect($order->fresh()->status)->not->toBe('cancelled');
});

it('blocks cancelling a partially-paid order', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['total' => 5000, 'status' => 'confirmed']);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 2000]);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect($order->fresh()->status)->toBe('confirmed');
});

it('cancels an unpaid order as before', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['status' => 'confirmed']);

    patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'cancelled'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($order->fresh()->status)->toBe('cancelled');
});

it('records a manual payment and rejects overpayment', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['total' => 5000]);

    postJson('/api/v1/admin/payments', ['order_id' => $order->id, 'amount' => 2000], ['Authorization' => "Bearer $token"])
        ->assertCreated()
        ->assertJsonPath('data.amount', 2000);

    postJson('/api/v1/admin/payments', ['order_id' => $order->id, 'amount' => 9900], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable();
});

it('defaults the payment method to cash when omitted', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['total' => 5000]);

    postJson('/api/v1/admin/payments', ['order_id' => $order->id, 'amount' => 2000], ['Authorization' => "Bearer $token"])
        ->assertCreated()
        ->assertJsonPath('data.method', 'cash');
});

it('stores an explicit cash payment method and stores amount as cents', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['total' => 5000]);

    postJson('/api/v1/admin/payments', ['order_id' => $order->id, 'amount' => 2000, 'method' => 'cash'], ['Authorization' => "Bearer $token"])
        ->assertCreated()
        ->assertJsonPath('data.method', 'cash')
        ->assertJsonPath('data.amount', 2000);
});

it('rejects an unsupported payment method', function (): void {
    $token = adminToken();
    $order = Order::factory()->create(['total' => 5000]);

    postJson('/api/v1/admin/payments', ['order_id' => $order->id, 'amount' => 2000, 'method' => 'card'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('method');

    expect($order->payments()->count())->toBe(0);
});

it('lists payments filtered by order', function (): void {
    $token = adminToken();
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id]);
    Payment::factory()->create(['order_id' => Order::factory()->create(['menu_id' => Menu::factory(['menu_date' => today()->subDay()])])->id]);

    $response = getJson("/api/v1/admin/payments?filter[order_id]={$payment->order_id}", ['Authorization' => "Bearer $token"])
        ->assertOk();

    expect(count($response->json('data.resource')))->toBe(1);
});

it('shows a customer with their order history and updates their details', function (): void {
    $token = adminToken();
    $customer = Customer::factory()->create(['name' => 'Old Name']);
    Order::factory()->create(['customer_id' => $customer->id]);

    getJson("/api/v1/admin/customers/{$customer->id}", ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonCount(1, 'data.orders');

    patchJson("/api/v1/admin/customers/{$customer->id}", ['name' => 'New Name'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('creates a menu with items and publishes it', function (): void {
    $token = adminToken();

    $menuId = postJson('/api/v1/admin/menus', ['menu_date' => '2026-12-01'], ['Authorization' => "Bearer $token"])
        ->assertCreated()
        ->assertJsonPath('data.published', false)
        ->json('data.id');

    postJson("/api/v1/admin/menus/{$menuId}/items", [
        'type' => 'main', 'name' => 'Butter Chicken', 'price' => 1500,
    ], ['Authorization' => "Bearer $token"])->assertCreated();

    patchJson("/api/v1/admin/menus/{$menuId}", ['publish' => true], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.published', true);
});

it('refuses to delete a menu referenced by an order', function (): void {
    $token = adminToken();
    $menu = Menu::factory()->create();
    $item = $menu->items()->create([
        'type' => 'main', 'name' => 'Butter Chicken', 'price' => 1500,
    ]);
    Order::factory()->create(['menu_id' => $menu->id]);

    deleteJson("/api/v1/admin/menus/{$menu->id}", [], ['Authorization' => "Bearer $token"])
        ->assertConflict()
        ->assertJsonPath('success', false);

    expect($menu->fresh())->not->toBeNull()
        ->and($item->fresh())->not->toBeNull();
});

it('deletes a menu with no orders along with its items', function (): void {
    $token = adminToken();
    $menu = Menu::factory()->create();
    $item = $menu->items()->create([
        'type' => 'main', 'name' => 'Butter Chicken', 'price' => 1500,
    ]);

    deleteJson("/api/v1/admin/menus/{$menu->id}", [], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($menu->fresh())->toBeNull()
        ->and($item->fresh())->toBeNull();
});

it('updates and deletes global settings', function (): void {
    $token = adminToken();

    putJson('/api/v1/admin/settings', ['store_phone' => '0411222333'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.store_phone', '0411222333');

    putJson('/api/v1/admin/settings', ['store_email' => 'not-an-email'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable();
});

it('rejects unknown settings keys with 422 and changes nothing', function (): void {
    $token = adminToken();

    putJson('/api/v1/admin/settings', ['store_phone' => '0411222333'], ['Authorization' => "Bearer $token"])
        ->assertOk();

    putJson('/api/v1/admin/settings', ['store_phone' => '0455666777', 'foo' => 'bar'], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('foo');

    // Partial updates of known keys still work.
    putJson('/api/v1/admin/settings', ['store_email' => 'store@example.com'], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.store_email', 'store@example.com');

    getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.store_phone', '0411222333');
});

it('logs out and invalidates the token', function (): void {
    $token = adminToken();
    $plainText = explode('|', $token, 2)[1];

    postJson('/api/v1/admin/logout', [], ['Authorization' => "Bearer $token"])->assertOk();
    expect(PersonalAccessToken::where('token', hash('sha256', $plainText))->exists())->toBeFalse();
});
