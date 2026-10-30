<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderAlreadyPaidException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ServiceUnavailableException;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\GetOrdersByUserRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * POST /orders: Checkout cart into a new order.
     *
     * @param CreateOrderRequest $request
     * @return JsonResponse
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $userId = (int) $request->validated('user_id');

        try {
            $order = $this->orderService->checkout($userId);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order,
            ], 201);
        } catch (EmptyCartException | ProductNotFoundException | InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (ServiceUnavailableException $e) {
            return $this->serviceUnavailableResponse($e);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'membuat pesanan');
        }
    }

    /**
     * GET /orders/{id}: Get order details with items.
     *
     * @param mixed $id
     * @return JsonResponse
     */
    public function show(mixed $id): JsonResponse
    {
        if (!ctype_digit((string) $id) || (int) $id <= 0) {
            return response()->json([
                'message' => "Pesanan dengan id {$id} tidak ditemukan",
            ], 404);
        }

        try {
            $order = $this->orderService->getOrder((int) $id);

            return response()->json([
                'message' => 'Berhasil mengambil pesanan',
                'data' => $order,
            ], 200);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'mengambil pesanan');
        }
    }

    /**
     * GET /orders/user/{userId}: Get list of orders for a user.
     * Optional ?status= filter.
     *
     * @param GetOrdersByUserRequest $request
     * @param mixed $userId
     * @return JsonResponse
     */
    public function byUser(GetOrdersByUserRequest $request, mixed $userId): JsonResponse
    {
        if (!ctype_digit((string) $userId) || (int) $userId <= 0) {
            return response()->json([
                'message' => 'Resource atau user tidak ditemukan',
            ], 404);
        }

        try {
            $status = $request->validated('status');
            $orders = $this->orderService->getUserOrders((int) $userId, $status);

            return response()->json([
                'message' => 'Berhasil mengambil daftar pesanan',
                'data' => $orders,
            ], 200);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'mengambil daftar pesanan');
        }
    }

    /**
     * PATCH /orders/{id}/pay: Simulate payment, change status from PENDING to PAID.
     *
     * @param mixed $id
     * @return JsonResponse
     */
    public function pay(mixed $id): JsonResponse
    {
        if (!ctype_digit((string) $id) || (int) $id <= 0) {
            return response()->json([
                'message' => "Pesanan dengan id {$id} tidak ditemukan",
            ], 404);
        }

        try {
            $order = $this->orderService->payOrder((int) $id);

            return response()->json([
                'message' => 'Pesanan berhasil dibayar',
                'data' => $order,
            ], 200);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (OrderAlreadyPaidException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'membayar pesanan');
        }
    }

    /**
     * Standardized 503 response for external service outage.
     * Technical details only shown when APP_DEBUG=true.
     *
     * @param ServiceUnavailableException $e
     * @return JsonResponse
     */
    private function serviceUnavailableResponse(ServiceUnavailableException $e): JsonResponse
    {
        Log::error('Service eksternal bermasalah', [
            'service' => $e->getServiceName(),
            'message' => $e->getMessage(),
        ]);

        $body = [
            'message' => $e->getMessage(),
        ];

        if (config('app.debug') && $e->getPrevious()) {
            $prev = $e->getPrevious();
            $body['error'] = $prev->getMessage();
            $body['location'] = $prev->getFile() . ':' . $prev->getLine();
        }

        return response()->json($body, 503);
    }

    /**
     * Standardized 500 response for unexpected server errors.
     * Technical details only shown when APP_DEBUG=true.
     *
     * @param Throwable $e
     * @param string $action
     * @return JsonResponse
     */
    private function errorResponse(Throwable $e, string $action): JsonResponse
    {
        Log::error("Gagal {$action}", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $body = [
            'message' => "Terjadi kesalahan internal saat {$action}",
        ];

        if (config('app.debug')) {
            $body['error'] = $e->getMessage();
            $body['location'] = $e->getFile() . ':' . $e->getLine();
        }

        return response()->json($body, 500);
    }
}
