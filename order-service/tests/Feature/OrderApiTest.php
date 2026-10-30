<?php

namespace Tests\Feature;

use App\Clients\CartServiceClient;
use App\Clients\ProductServiceClient;
use App\Exceptions\ServiceUnavailableException;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test POST /orders returns 422 if body does not have valid user_id.
     */
    public function test_post_orders_validation_error_when_missing_user_id(): void
    {
        $response = $this->postJson('/orders', []);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Data yang dikirim tidak valid',
                'errors' => [
                    'user_id' => [
                        'Field user_id wajib diisi',
                    ],
                ],
            ]);
    }

    /**
     * Test POST /orders returns 409 when cart is empty.
     */
    public function test_post_orders_fails_when_cart_is_empty(): void
    {
        $cartMock = Mockery::mock(CartServiceClient::class);
        $cartMock->shouldReceive('getCart')->with(7)->andReturn([
            'user_id' => 7,
            'items' => [],
            'total_quantity' => 0,
        ]);
        $this->app->instance(CartServiceClient::class, $cartMock);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Keranjang belanja masih kosong',
            ]);
    }

    /**
     * Test POST /orders returns 409 when product stock is insufficient.
     */
    public function test_post_orders_fails_when_stock_insufficient(): void
    {
        $cartMock = Mockery::mock(CartServiceClient::class);
        $cartMock->shouldReceive('getCart')->with(7)->andReturn([
            'user_id' => 7,
            'items' => [
                ['id' => 1, 'user_id' => 7, 'product_id' => 3, 'quantity' => 11],
            ],
            'total_quantity' => 11,
        ]);
        $this->app->instance(CartServiceClient::class, $cartMock);

        $productMock = Mockery::mock(ProductServiceClient::class);
        $productMock->shouldReceive('getProduct')->with(3)->andReturn([
            'id' => 3,
            'name' => 'Monitor 24 Inch',
            'price' => '1800000.00',
            'stock' => 10,
        ]);
        $this->app->instance(ProductServiceClient::class, $productMock);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Stok Monitor 24 Inch tidak mencukupi: diminta 11, tersedia 10',
            ]);
    }

    /**
     * Test POST /orders returns 503 when product service is down.
     */
    public function test_post_orders_fails_when_product_service_down(): void
    {
        $cartMock = Mockery::mock(CartServiceClient::class);
        $cartMock->shouldReceive('getCart')->with(7)->andReturn([
            'user_id' => 7,
            'items' => [
                ['id' => 1, 'user_id' => 7, 'product_id' => 1, 'quantity' => 2],
            ],
            'total_quantity' => 2,
        ]);
        $this->app->instance(CartServiceClient::class, $cartMock);

        $productMock = Mockery::mock(ProductServiceClient::class);
        $productMock->shouldReceive('getProduct')->with(1)->andThrow(
            new ServiceUnavailableException('Product service', 'Product service tidak dapat dihubungi, coba lagi nanti')
        );
        $this->app->instance(ProductServiceClient::class, $productMock);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Product service tidak dapat dihubungi, coba lagi nanti',
            ]);
    }

    /**
     * Test POST /orders succeeds and creates snapshot items.
     */
    public function test_post_orders_succeeds(): void
    {
        $cartMock = Mockery::mock(CartServiceClient::class);
        $cartMock->shouldReceive('getCart')->with(7)->andReturn([
            'user_id' => 7,
            'items' => [
                ['id' => 1, 'user_id' => 7, 'product_id' => 1, 'quantity' => 2],
                ['id' => 2, 'user_id' => 7, 'product_id' => 2, 'quantity' => 1],
            ],
            'total_quantity' => 3,
        ]);
        $cartMock->shouldReceive('clearCart')->with(7)->andReturn(true);
        $this->app->instance(CartServiceClient::class, $cartMock);

        $productMock = Mockery::mock(ProductServiceClient::class);
        $productMock->shouldReceive('getProduct')->with(1)->andReturn([
            'id' => 1,
            'name' => 'Keyboard Mekanikal',
            'price' => '450000.00',
            'stock' => 25,
        ]);
        $productMock->shouldReceive('getProduct')->with(2)->andReturn([
            'id' => 2,
            'name' => 'Mouse Wireless',
            'price' => '150000.00',
            'stock' => 40,
        ]);
        $this->app->instance(ProductServiceClient::class, $productMock);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Pesanan berhasil dibuat')
            ->assertJsonPath('data.user_id', 7)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.total_amount', 1050000)
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * Test GET /orders/{id} returns single order details.
     */
    public function test_get_order_by_id_succeeds(): void
    {
        $order = Order::create([
            'user_id' => 7,
            'status' => 'PENDING',
            'total_amount' => 450000,
        ]);
        $order->items()->create([
            'product_id' => 1,
            'product_name' => 'Keyboard Mekanikal',
            'unit_price' => 450000,
            'quantity' => 1,
            'subtotal' => 450000,
        ]);

        $response = $this->getJson("/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Berhasil mengambil pesanan')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user_id', 7);
    }

    /**
     * Test GET /orders/{id} returns 404 when order does not exist.
     */
    public function test_get_order_not_found(): void
    {
        $response = $this->getJson('/orders/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Pesanan dengan id 999 tidak ditemukan',
            ]);
    }

    /**
     * Test GET /orders/user/{userId} with valid and invalid status filters.
     */
    public function test_get_orders_by_user_with_filter(): void
    {
        Order::create(['user_id' => 7, 'status' => 'PENDING', 'total_amount' => 100000]);
        Order::create(['user_id' => 7, 'status' => 'PAID', 'total_amount' => 200000]);

        // Success filter PENDING
        $resPending = $this->getJson('/orders/user/7?status=PENDING');
        $resPending->assertStatus(200)
            ->assertJsonPath('message', 'Berhasil mengambil daftar pesanan')
            ->assertJsonCount(1, 'data');

        // Invalid filter status 422
        $resInvalid = $this->getJson('/orders/user/7?status=SHIPPED');
        $resInvalid->assertStatus(422)
            ->assertJson([
                'message' => 'Data yang dikirim tidak valid',
                'errors' => [
                    'status' => [
                        'Status harus PENDING atau PAID',
                    ],
                ],
            ]);
    }

    /**
     * Test PATCH /orders/{id}/pay successfully pays order and rejects paying twice.
     */
    public function test_patch_order_pay_and_idempotency_check(): void
    {
        $order = Order::create([
            'user_id' => 7,
            'status' => 'PENDING',
            'total_amount' => 150000,
        ]);

        // First pay: success (200)
        $payRes = $this->patchJson("/orders/{$order->id}/pay");
        $payRes->assertStatus(200)
            ->assertJsonPath('message', 'Pesanan berhasil dibayar')
            ->assertJsonPath('data.status', 'PAID');

        // Second pay: fails 409
        $payRes2 = $this->patchJson("/orders/{$order->id}/pay");
        $payRes2->assertStatus(409)
            ->assertJson([
                'message' => 'Pesanan berstatus PAID, hanya pesanan PENDING yang dapat dibayar',
            ]);
    }
}
