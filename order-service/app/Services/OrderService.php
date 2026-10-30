<?php

namespace App\Services;

use App\Clients\CartServiceClient;
use App\Clients\ProductServiceClient;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderAlreadyPaidException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartClient,
        private ProductServiceClient $productClient,
    ) {
    }

    /**
     * Checkout: convert user's cart contents into a new order.
     *
     * @param int $userId
     * @return Order
     * @throws EmptyCartException
     * @throws ProductNotFoundException
     * @throws InsufficientStockException
     */
    public function checkout(int $userId): Order
    {
        // 1. Ambil isi keranjang user dari cart-service
        $cartData = $this->cartClient->getCart($userId);
        $cartItems = $cartData['items'] ?? [];

        // 2. Periksa aturan bisnis: keranjang tidak boleh kosong
        if (empty($cartItems)) {
            Log::info('Checkout dibatalkan karena keranjang kosong', ['user_id' => $userId]);
            throw new EmptyCartException('Keranjang belanja masih kosong');
        }

        $preparedItems = [];
        $totalAmount = 0.0;

        // 3. Untuk setiap item, ambil data produk dari product-service dan periksa stok
        foreach ($cartItems as $item) {
            $productId = (int) $item['product_id'];
            $requestedQty = (int) $item['quantity'];

            $product = $this->productClient->getProduct($productId);

            if ($product === null) {
                Log::warning('Produk di keranjang tidak ditemukan di product-service', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]);
                throw new ProductNotFoundException($productId, "Produk dengan ID {$productId} tidak ditemukan");
            }

            $productName = (string) ($product['name'] ?? "Produk #{$productId}");
            $availableStock = (int) ($product['stock'] ?? 0);

            if ($requestedQty > $availableStock) {
                Log::warning('Stok produk tidak mencukupi untuk checkout', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'diminta' => $requestedQty,
                    'tersedia' => $availableStock,
                ]);
                throw new InsufficientStockException($productName, $requestedQty, $availableStock);
            }

            // Ubah harga berbentuk string dari product-service ke angka
            $unitPrice = (float) $product['price'];
            $subtotal = $unitPrice * $requestedQty;
            $totalAmount += $subtotal;

            $preparedItems[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'unit_price' => $unitPrice,
                'quantity' => $requestedQty,
                'subtotal' => $subtotal,
            ];
        }

        // 4. Simpan pesanan beserta item-itemnya dalam SATU transaksi database
        $order = DB::transaction(function () use ($userId, $totalAmount, $preparedItems) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
            ]);

            foreach ($preparedItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        $order->load('items');

        Log::info('Pesanan berhasil dibuat di database', [
            'order_id' => $order->id,
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'items_count' => count($preparedItems),
        ]);

        // 5. Minta cart-service mengosongkan keranjang user
        // Catatan: Jika gagal, pesanan tidak boleh dibatalkan, tetap lanjut dan catat di log
        try {
            $cleared = $this->cartClient->clearCart($userId);
            if (!$cleared) {
                Log::warning('Keranjang user gagal dikosongkan setelah order dibuat', [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Exception saat mengosongkan keranjang setelah order dibuat', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    /**
     * Get single order details with items loaded.
     *
     * @param int $id
     * @return Order
     * @throws OrderNotFoundException
     */
    public function getOrder(int $id): Order
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    /**
     * Get list of orders for a user, sorted descending by created_at.
     * Optional status filter.
     *
     * @param int $userId
     * @param string|null $status
     * @return Collection
     */
    public function getUserOrders(int $userId, ?string $status = null): Collection
    {
        $query = Order::with('items')
            ->forUser($userId)
            ->orderByDesc('created_at');

        if ($status !== null && $status !== '') {
            $query->withStatus($status);
        }

        return $query->get();
    }

    /**
     * Pay order simulation: change status from PENDING to PAID.
     *
     * @param int $id
     * @return Order
     * @throws OrderNotFoundException
     * @throws OrderAlreadyPaidException
     */
    public function payOrder(int $id): Order
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            throw new OrderNotFoundException($id);
        }

        if (!$order->isPending()) {
            throw new OrderAlreadyPaidException($order->status);
        }

        $order->update([
            'status' => Order::STATUS_PAID,
        ]);

        $order->refresh()->load('items');

        Log::info('Pesanan berhasil dibayar', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
        ]);

        return $order;
    }
}
