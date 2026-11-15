<?php

// tests/Feature/MyAccountTest.php

use App\Models\Customer;
use App\Models\GlobalSetting;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderingWindowService;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\ClerkJwt;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, string>
 */
function authHeadersFor(string $email, array $overrides = []): array
{
    return ['Authorization' => 'Bearer '.ClerkJwt::token($email, $overrides)];
}

beforeEach(function () {
    config()->set('clerk.issuer', ClerkJwt::ISSUER);
    config()->set('clerk.allowed_origins', [ClerkJwt::ORIGIN]);

    // Serve the fake JWKS locally — CachedKeySet uses a real Guzzle
    // client, which Laravel's Http::fake() does not intercept.
    $jwks = json_encode(ClerkJwt::jwks());
    $fake = new class($jwks) extends Client
    {
        public function __construct(private readonly string $jwks)
        {
            parent::__construct();
        }

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            return new Response(200, [], $this->jwks);
        }
    };

    $this->app->instance(Client::class, $fake);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('rejects a request without a bearer token', function () {
    getJson('/api/v1/my-account')->assertUnauthorized();
});

it('returns 401, not 500, for a malformed bearer token', function () {
    getJson('/api/v1/my-account', ['Authorization' => 'Bearer not-a-real-jwt'])
        ->assertUnauthorized();
});

it('returns 401 for a token signed by the wrong key', function () {
    $forged = JWT::encode([
        'iss' => ClerkJwt::ISSUER,
        'azp' => ClerkJwt::ORIGIN,
        'exp' => time() + 3600,
        'email' => 'amol@example.com',
        'email_verified' => true,
    ], str_repeat('x', 64), 'HS256');

    getJson('/api/v1/my-account', ['Authorization' => 'Bearer '.$forged])
        ->assertUnauthorized();
});

it('returns 401, not 500, for an invalid bearer token on order placement', function () {
    postJson('/api/v1/orders', [], ['Authorization' => 'Bearer garbage'])
        ->assertUnauthorized();
});

it('rejects a token with an unverified email claim', function () {
    getJson('/api/v1/my-account', authHeadersFor('amol@example.com', [
        'email_verified' => false,
    ]))->assertUnauthorized();
});

it('rejects a token from a disallowed origin', function () {
    getJson('/api/v1/my-account', authHeadersFor('amol@example.com', [
        'azp' => 'https://evil.example.com',
    ]))->assertUnauthorized();
});

it('auto-provisions the customer on first my-account request', function () {
    getJson('/api/v1/my-account', authHeadersFor('amol@example.com', [
        'name' => 'Amol W',
    ]))
        ->assertOk()
        ->assertJsonPath('data.email', 'amol@example.com')
        ->assertJsonPath('data.name', 'Amol W');

    expect(Customer::where('email', 'amol@example.com')->exists())->toBeTrue();
});

it('returns the existing email-matched customer', function () {
    $customer = Customer::create([
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'suburb' => 'Te Aro',
    ]);

    getJson('/api/v1/my-account', authHeadersFor('AMOL@example.com'))
        ->assertOk()
        ->assertJsonPath('data.phone', '0211234567');

    expect(Customer::count())->toBe(1);
});

it('updates contact and delivery details via patch', function () {
    Customer::create(['name' => 'Amol W', 'email' => 'amol@example.com']);
    GlobalSetting::current();

    patchJson('/api/v1/my-account', [
        'phone' => '0219998888',
        'address' => '12 Cuba St',
        'suburb' => 'Te Aro',
    ], authHeadersFor('amol@example.com'))
        ->assertOk()
        ->assertJsonPath('data.phone', '0219998888')
        ->assertJsonPath('data.suburb', 'Te Aro');
});

it('rejects a suburb outside the delivery list', function () {
    Customer::create(['name' => 'Amol W', 'email' => 'amol@example.com']);
    GlobalSetting::current();

    patchJson('/api/v1/my-account', [
        'suburb' => 'Nowhere',
    ], authHeadersFor('amol@example.com'))->assertUnprocessable();
});

it('lists all orders for the email-matched customer, newest first', function () {
    $customer = Customer::create(['name' => 'Amol W', 'email' => 'amol@example.com']);
    $other = Customer::create(['name' => 'Sam', 'email' => 'sam@example.com']);

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));
    $menu = Menu::create([
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

    foreach ([[$customer, 'a'], [$other, 'b'], [$customer, 'c']] as [$who, $token]) {
        $order = new Order;
        $order->fill([
            'menu_id' => $menu->id,
            'customer_id' => $who->id,
            'status' => Order::STATUS_CONFIRMED,
            'fulfilment' => 'pickup',
            'placed_at' => now()->subMinutes(rand(1, 5)),
            'expires_at' => Carbon::today(OrderingWindowService::TIMEZONE)->setTime(23, 59, 59),
        ]);
        $order->manage_token = bin2hex(random_bytes(4)).$token;
        $order->save();
    }

    getJson('/api/v1/my-orders', authHeadersFor('amol@example.com'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns an empty list without creating a customer', function () {
    getJson('/api/v1/my-orders', authHeadersFor('new@example.com'))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect(Customer::count())->toBe(0);
});

it('links a bearer-authenticated order to the email-matched customer and rejects a mismatched payload email', function () {
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));
    GlobalSetting::current();

    $menu = Menu::create([
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

    $payload = [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'suburb' => 'Te Aro',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ];

    postJson('/api/v1/orders', $payload, authHeadersFor('amol@example.com'))->assertCreated();

    $customer = Customer::where('email', 'amol@example.com')->first();
    expect($customer->orders())->count()->toBe(1);

    $mismatched = array_merge($payload, ['email' => 'someone-else@example.com']);
    postJson('/api/v1/orders', $mismatched, authHeadersFor('amol@example.com'))
        ->assertUnprocessable();
});
