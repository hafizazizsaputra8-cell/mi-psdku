<?php

namespace App\Clients;

use App\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductServiceClient
{
    private string $baseUrl;
    private float $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.product.url', 'http://product-service:3000'), '/');
        $this->timeout = min((float) config('services.product.timeout', 5.0), 5.0);
    }

    /**
     * Retrieve product details by ID from product-service.
     * Returns null if product is not found (404).
     * Throws ServiceUnavailableException on network/server errors.
     *
     * @param int $productId
     * @return array|null
     * @throws ServiceUnavailableException
     */
    public function getProduct(int $productId): ?array
    {
        $url = "{$this->baseUrl}/products/{$productId}";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url);

            if ($response->status() === 404) {
                return null;
            }

            if ($response->status() >= 500) {
                Log::error('Product service mengembalikan error 5xx', [
                    'product_id' => $productId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new ServiceUnavailableException('Product service', 'Product service tidak dapat dihubungi, coba lagi nanti');
            }

            if ($response->successful()) {
                $payload = $response->json();
                return $payload['data'] ?? null;
            }

            Log::error('Product service response tidak terduga', [
                'product_id' => $productId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ServiceUnavailableException('Product service', 'Product service tidak dapat dihubungi, coba lagi nanti');
        } catch (ServiceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal menghubungi product-service', [
                'product_id' => $productId,
                'url' => $url,
                'timeout' => $this->timeout,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceUnavailableException('Product service', 'Product service tidak dapat dihubungi, coba lagi nanti', $e);
        }
    }
}
