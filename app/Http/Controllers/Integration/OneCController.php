<?php

namespace App\Http\Controllers\Integration;

use App\Models\Order;
use App\Services\OneCIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OneCController
{
    public function __construct(private readonly OneCIntegrationService $service) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => '1c-integration',
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:new,in_process,done,canceled',
            'only_not_exported' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $orders = $this->service->getOrdersForExport(
            $validated['status'] ?? null,
            (bool) ($validated['only_not_exported'] ?? true),
            (int) ($validated['limit'] ?? 100)
        );

        return response()->json([
            'count' => $orders->count(),
            'orders' => $orders->map(fn(Order $order) => $this->service->mapOrderForOneC($order))->values(),
        ]);
    }

    public function markOrdersExported(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|min:1',
        ]);

        $updated = $this->service->markOrdersExported($validated['order_ids']);

        return response()->json([
            'updated' => $updated,
        ]);
    }

    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,in_process,done,canceled',
        ]);

        $order = $this->service->updateOrderStatus($order, $validated['status']);

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
        ]);
    }

    public function syncStocks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.external_id' => 'nullable|string|max:255',
            'items.*.product_id' => 'nullable|integer|min:1',
        ]);

        $result = $this->service->syncStocks($validated['items']);

        return response()->json($result);
    }
}
