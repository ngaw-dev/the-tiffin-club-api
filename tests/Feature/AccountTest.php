<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function orderFor(string $email, string $phone = '0211234567'): void
{
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $menu = Menu::query()->whereDate('menu_date', today(OrderingWindowService::TIMEZONE)->toDateString())->first()
        ?? Menu::create([
            'menu_date' => today(OrderingWindowService::TIMEZONE)->toDateString(),
            'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
            'cutoff_time' => '11:00',
        ]);
    $main = $menu->items()->create([
        'type' => MenuItem::TYPE_MAIN,
        'name' => 'Butter Chicken',
        'price' => 1600,
        'sort_order' => 1,
    ]);

    postJson('/api/v1/orders', [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => $email,
        'phone' => $phone,
        'suburb' => 'Te Aro',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();
}

afterEach(function () {
    Carbon::setTestNow();
});

function tokenFor(string $email, string $password = 'super-secret-1'): string
{
    postJson('/api/v1/account/register', ['email' => $email, 'password' => $password]);
    $login = postJson('/api/v1/account/login', ['email' => $email, 'password' => $password]);

    return $login->json('data.token');
}

it('registers an account and returns a bearer token', function () {
    $response = postJson('/api/v1/account/register', [
        'email' => 'amol@example.com',
        'password' => 'super-secret-1',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('rejects duplicate registration with the same email', function () {
    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'super-secret-1'])->assertCreated();
    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'another-pass-2'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function () {
    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'super-secret-1']);

    postJson('/api/v1/account/login', ['email' => 'amol@example.com', 'password' => 'super-secret-1'])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('rejects login with wrong password', function () {
    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'super-secret-1']);

    postJson('/api/v1/account/login', ['email' => 'amol@example.com', 'password' => 'wrong-pass-3'])
        ->assertUnprocessable();
});

it('lists an account\'s orders including guest orders placed before the account existed', function () {
    // Guest order first, account later — retroactive email match.
    orderFor('amol@example.com');

    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'super-secret-1']);
    $token = postJson('/api/v1/account/login', ['email' => 'amol@example.com', 'password' => 'super-secret-1'])->json('data.token');

    getJson('/api/v1/account/orders', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'amol@example.com');
});

it('does not list other people\'s orders', function () {
    orderFor('amol@example.com');
    orderFor('someone-else@example.com', '0219999999');

    postJson('/api/v1/account/register', ['email' => 'amol@example.com', 'password' => 'super-secret-1']);
    $token = postJson('/api/v1/account/login', ['email' => 'amol@example.com', 'password' => 'super-secret-1'])->json('data.token');

    getJson('/api/v1/account/orders', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('requires authentication to list orders', function () {
    getJson('/api/v1/account/orders')
        ->assertUnauthorized();
});

it('returns prefill details from the most recent order', function () {
    orderFor('amol@example.com');

    // A second, newer order with different details.
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 30));
    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol Wickramasinghe',
        'email' => 'amol@example.com',
        'items' => [['id' => MenuItem::where('type', 'main')->first()->id, 'quantity' => 1]],
    ])->assertCreated();

    $token = tokenFor('amol@example.com');

    getJson('/api/v1/account/prefill', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.name', 'Amol Wickramasinghe');
});

it('links guest orders case-insensitively by email', function () {
    orderFor('Amol@Example.com');

    $token = tokenFor('amol@example.com');

    getJson('/api/v1/account/orders', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('keeps ordering guest-first — no account required to place, view, edit or cancel', function () {
    orderFor('amol@example.com');

    $order = Order::first();

    // Every customer route works without any Authorization header.
    getJson("/api/v1/orders/{$order->manage_token}")->assertOk();
    patchJson("/api/v1/orders/{$order->manage_token}", ['name' => 'Renamed'])->assertOk();
    deleteJson("/api/v1/orders/{$order->manage_token}")->assertOk();
});
