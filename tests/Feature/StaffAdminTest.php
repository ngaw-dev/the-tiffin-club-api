<?php

use App\Models\Customer;
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
    // The orders page defaults to the delivery filter, so pin fulfilment
    $order = Order::factory()->create(['fulfilment' => 'delivery']);

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

it('blocks non-staff users from the payments page', function (): void {
    $intruder = User::factory()->create(['email' => 'random@example.com']);

    actingAs($intruder)->get('/staff/payments')->assertForbidden();

    get('/staff/payments')->assertForbidden();
});

it('summarises what each customer owes across their orders', function (): void {
    staffUser();
    $customer = Customer::create(['name' => 'Weekly Wanda', 'address' => '1 Elm St', 'suburb' => 'Testville']);

    // Two open orders, partially paid once: owes 2000 + 1500 - 500 = 3000
    $first = Order::factory()->create(['customer_id' => $customer->id, 'total' => 2000, 'menu_id' => Menu::factory(['menu_date' => today()])]);
    $second = Order::factory()->create(['customer_id' => $customer->id, 'total' => 1500, 'menu_id' => Menu::factory(['menu_date' => today()->subDay()])]);
    Payment::create(['order_id' => $first->id, 'customer_id' => $customer->id, 'amount' => 500, 'method' => 'cash', 'paid_at' => now()]);

    actingAs(User::where('email', 'staff@admin.com')->first())
        ->get('/staff/payments')
        ->assertOk()
        ->assertSee('Weekly Wanda')
        ->assertSee('Owes: $30.00');
});

it('excludes cancelled orders from customer due and paid totals', function (): void {
    staffUser();
    $customer = Customer::create(['name' => 'Careful Carl', 'address' => '2 Elm St', 'suburb' => 'Testville']);

    $open = Order::factory()->create(['customer_id' => $customer->id, 'total' => 1000, 'menu_id' => Menu::factory(['menu_date' => today()])]);
    $cancelled = Order::factory()->create(['customer_id' => $customer->id, 'total' => 5000, 'status' => 'cancelled', 'menu_id' => Menu::factory(['menu_date' => today()->subDay()])]);
    Payment::create(['order_id' => $cancelled->id, 'customer_id' => $customer->id, 'amount' => 2000, 'method' => 'cash', 'paid_at' => now()]);

    actingAs(User::where('email', 'staff@admin.com')->first())
        ->get('/staff/payments')
        ->assertOk()
        ->assertSee('Owes: $10.00')
        ->assertDontSee('Credit:');
});

it('shows a credit badge when a customer has overpaid', function (): void {
    staffUser();
    $customer = Customer::create(['name' => 'Prepaid Priya', 'address' => '3 Elm St', 'suburb' => 'Testville']);

    $order = Order::factory()->create(['customer_id' => $customer->id, 'total' => 1000, 'menu_id' => Menu::factory(['menu_date' => today()])]);
    // Two payments on the same order: 600 + 600 = 1200 against a 1000 total
    foreach ([600, 600] as $amount) {
        Payment::create(['order_id' => $order->id, 'customer_id' => $customer->id, 'amount' => $amount, 'method' => 'cash', 'paid_at' => now()]);
    }

    actingAs(User::where('email', 'staff@admin.com')->first())
        ->get('/staff/payments')
        ->assertOk()
        ->assertSee('Credit: $2.00');
});

it('lists customers with the largest outstanding balance first', function (): void {
    staffUser();
    $big = Customer::create(['name' => 'Big Balancer', 'address' => '4 Elm St', 'suburb' => 'Testville']);
    $small = Customer::create(['name' => 'Small Saver', 'address' => '5 Elm St', 'suburb' => 'Testville']);

    Order::factory()->create(['customer_id' => $big->id, 'total' => 9000, 'menu_id' => Menu::factory(['menu_date' => today()])]);
    Order::factory()->create(['customer_id' => $small->id, 'total' => 1000, 'menu_id' => Menu::factory(['menu_date' => today()->addDay()])]);

    $response = actingAs(User::where('email', 'staff@admin.com')->first())
        ->get('/staff/payments');

    $response->assertOk();
    expect(strpos($response->getContent(), 'Big Balancer'))->toBeLessThan(strpos($response->getContent(), 'Small Saver'));
});
it('returns staff to the payments page after settling from it', function (): void {
    staffUser();
    $customer = Customer::create(['name' => 'Redirect Rita', 'address' => '6 Elm St', 'suburb' => 'Testville']);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'total' => 2000]);

    actingAs(User::where('email', 'staff@admin.com')->first())
        ->from('/staff/payments')
        ->post("/staff/orders/{$order->id}/payments", ['amount' => 20])
        ->assertRedirect('/staff/payments');
});