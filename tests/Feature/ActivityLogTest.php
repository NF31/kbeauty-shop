<?php

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\RefundResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('a manual stock edit is logged with the admin as causer', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 8,
        'is_default' => $variant->is_default,
    ])->assertRedirect();

    $activity = Activity::query()->forSubject($variant->fresh())->inLog('stock')->sole();

    expect($activity->causer_id)->toBe($this->admin->id);
    expect($activity->attribute_changes['old']['stock_quantity'])->toBe(5);
    expect($activity->attribute_changes['attributes']['stock_quantity'])->toBe(8);
});

test('a variant update that does not change stock is not logged', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 7]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 7,
        'is_default' => $variant->is_default,
    ])->assertRedirect();

    expect(Activity::query()->forSubject($variant->fresh())->inLog('stock')->count())->toBe(0);
});

test('an order status change is logged with the admin as causer', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($this->admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => OrderStatus::Processing->value])
        ->assertRedirect();

    $activity = Activity::query()->forSubject($order->fresh())->inLog('order')->sole();

    expect($activity->causer_id)->toBe($this->admin->id);
    expect($activity->attribute_changes['old']['status'])->toBe(OrderStatus::Paid->value);
    expect($activity->attribute_changes['attributes']['status'])->toBe(OrderStatus::Processing->value);
});

test('a refund is logged', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total_cents' => 5000]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Succeeded,
        'amount_cents' => 5000,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('refund')->once()->andReturn(new RefundResult(id: 're_fake123', status: 'succeeded'));
    });

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 5000, 'reason' => 'Article manquant'])
        ->assertRedirect();

    $refund = Refund::query()->where('order_id', $order->id)->sole();

    $activity = Activity::query()->forSubject($refund)->inLog('refund')->sole();

    expect($activity->causer_id)->toBe($this->admin->id);
    expect($activity->attribute_changes['attributes']['amount_cents'])->toBe(5000);
    expect($activity->attribute_changes['attributes']['reason'])->toBe('Article manquant');
});

test('publishing a product is logged with the admin as causer', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
        'name' => $product->name,
        'description' => $product->description,
        'ingredients_inci' => 'Aqua, Glycerin, Niacinamide.',
        'status' => 'published',
    ])->assertRedirect();

    $activity = Activity::query()->forSubject($product->fresh())->inLog('product')->sole();

    expect($activity->causer_id)->toBe($this->admin->id);
    expect($activity->attribute_changes['old']['status'])->toBe('draft');
    expect($activity->attribute_changes['attributes']['status'])->toBe('published');
});

test('updating a product without changing its status is not logged', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
        'name' => 'Nom modifie',
        'description' => $product->description,
        'status' => 'draft',
    ])->assertRedirect();

    expect(Activity::query()->forSubject($product->fresh())->inLog('product')->count())->toBe(0);
});

test('a guest is redirected to login from the activity log page', function () {
    $this->get('/admin/activity-log')->assertRedirect('/login');
});

test('the staff role cannot view the activity log page', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get('/admin/activity-log')->assertForbidden();
});

test('the admin role can view the activity log page and see a logged stock change', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 8,
        'is_default' => $variant->is_default,
    ]);

    $this->actingAs($this->admin)->get('/admin/activity-log')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/activity-log/index')
            ->where('activities.data.0.logName', 'stock')
            ->where('activities.data.0.subjectLabel', 'Variante '.$variant->sku)
            ->where('activities.data.0.causerName', $this->admin->name)
            ->where('activities.data.0.changes.0', ['field' => 'Stock', 'old' => '5', 'new' => '8']));
});

test('the activity log page can be filtered by log name', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5]);
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 8,
        'is_default' => $variant->is_default,
    ]);

    $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/status", ['status' => OrderStatus::Processing->value]);

    $this->actingAs($this->admin)->get('/admin/activity-log?status=order')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => count($rows) === 1)
            ->where('activities.data.0.logName', 'order'));
});
