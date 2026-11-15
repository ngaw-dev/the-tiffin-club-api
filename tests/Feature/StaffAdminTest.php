<?php

use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function staffUser(): User
{
    return User::query()->create([
        'name' => 'Staff',
        'email' => 'staff@admin.com',
        'password' => Hash::make('password'),
    ]);
}

it('blocks non-staff users from the orders page', function (): void {
    $intruder = User::factory()->create(['email' => 'random@example.com']);

    actingAs($intruder)->get('/staff/orders')->assertForbidden();

    get('/staff/orders')->assertForbidden();
});

it('logs staff in and lists orders', function (): void {
    staffUser();
    $order = Order::factory()->create();

    post('/staff/login', ['email' => 'staff@admin.com', 'password' => 'password'])
        ->assertRedirect('/staff/orders');

    get('/staff/orders')->assertOk()->assertSee($order->customer->name);
});

it('shows order status stats on the orders page', function (): void {
    Order::factory()->create(['status' => 'ready_for_pickup']);
    Order::factory()->create(['status' => 'ready_for_pickup', 'menu_id' => Menu::factory(['menu_date' => today()->addDay()])]);
    Order::factory()->create(['status' => 'cancelled', 'menu_id' => Menu::factory(['menu_date' => today()->addDays(2)])]);

    actingAs(staffUser())
        ->get('/staff/orders')
        ->assertOk()
        ->assertSee('ready for pickup: 2')
        ->assertSee('cancelled: 1');
});

it('lets staff change order status to any state', function (): void {
    $order = Order::factory()->create();

    actingAs(staffUser())
        ->patch("/staff/orders/{$order->id}/status", ['status' => 'ready_for_pickup'])
        ->assertRedirect();

    expect($order->refresh()->status)->toBe('ready_for_pickup');
});

it('rejects an invalid status', function (): void {
    $order = Order::factory()->create();

    actingAs(staffUser())
        ->patch("/staff/orders/{$order->id}/status", ['status' => 'teleported'])
        ->assertSessionHasErrors('status');
});

it('records a partial payment without touching status', function (): void {
    $order = Order::factory()->create(['total' => 2000]);

    actingAs(staffUser())
        ->post("/staff/orders/{$order->id}/payments", ['amount' => 5.50])
        ->assertRedirect();

    $payment = Payment::query()->sole();
    expect($payment->amount)->toBe(550)
        ->and($payment->customer_id)->toBe($order->customer_id)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBe('confirmed');
});

it('rejects a payment above the outstanding amount', function (): void {
    $order = Order::factory()->create(['total' => 2000]);

    actingAs(staffUser())
        ->post("/staff/orders/{$order->id}/payments", ['amount' => 30])
        ->assertSessionHasErrors('amount');

    expect(Payment::count())->toBe(0);
});
