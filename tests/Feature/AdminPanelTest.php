<?php

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('guests cannot reach the admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('records a staff audit row when an order is edited via the panel', function () {
    $order = Order::factory()
        ->for(Customer::create(['name' => 'Test Customer']))
        ->for(Menu::factory()->create())
        ->create(['status' => Order::STATUS_CONFIRMED]);

    Livewire::actingAs(User::factory()->create())
        ->test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['status' => Order::STATUS_READY_FOR_PICKUP])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->fresh()->status)->toBe(Order::STATUS_READY_FOR_PICKUP);
    expect($order->fresh()->audits->last())
        ->actor->toBe('staff')
        ->action->toBe('edited')
        ->changes->toMatchArray(['status' => Order::STATUS_READY_FOR_PICKUP]);
});
