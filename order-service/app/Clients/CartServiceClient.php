<?php

namespace App\Clients;

use App\Exceptions\ServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartServiceClient
{
    private string $baseUrl;
    private float $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.cart.url', 'http://cart-service:3001'), '/');
        $this->timeout = min((float) config('services.cart.timeout', 5.0), 5.0);
    }

    /**
     * Retrieve cart items for a given user from cart-service.
     *
     * @param int $userId
     * @return array
     * @throws ServiceUnavailableException
     */
    public function getCart(int $userId): array
    {
        $url = "{$this->baseUrl}/carts/{$userId}";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url);

            if ($response->status() >= 500) {
                Log::error('Cart service mengembalikan error 5xx', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new ServiceUnavailableException('Cart service', 'Cart service tidak dapat dihubungi, coba lagi nanti');
            }

            if ($response->status() === 404) {
                return [
                    'user_id' => $userId,
                    'items' => [],
                    'total_quantity' => 0,
                ];
            }

            if ($response->successful()) {
                $payload = $response->json();
                return $payload['data'] ?? [
                    'user_id' => $userId,
                    'items' => [],
                    'total_quantity' => 0,
                ];
            }

            Log::error('Cart service response tidak terduga', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ServiceUnavailableException('Cart service', 'Cart service tidak dapat dihubungi, coba lagi nanti');
        } catch (ServiceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal menghubungi cart-service saat mengambil keranjang', [
                'user_id' => $userId,
                'url' => $url,
                'timeout' => $this->timeout,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceUnavailableException('Cart service', 'Cart service tidak dapat dihubungi, coba lagi nanti', $e);
        }
    }

    /**
     * Request cart-service to empty user's cart.
     * If request fails, logs warning and returns false without interrupting caller.
     *
     * @param int $userId
     * @return bool
     */
    public function clearCart(int $userId): bool
    {
        $url = "{$this->baseUrl}/carts/{$userId}";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->delete($url);

            if ($response->successful()) {
                Log::info('Berhasil mengosongkan keranjang di cart-service', [
                    'user_id' => $userId,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::warning('Cart-service merespons status non-200 saat mengosongkan keranjang', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::warning('Keranjang gagal dikosongkan karena cart-service tidak merespons', [
                'user_id' => $userId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
