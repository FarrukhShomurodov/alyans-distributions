<?php

namespace App\Http\Controllers\Integration;

use App\Services\SovaIntegrationService;
use Illuminate\Http\JsonResponse;

class SovaController
{
    public function __construct(private readonly SovaIntegrationService $service) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'sova-integration',
        ]);
    }

    public function syncAll(): JsonResponse
    {
        $result = $this->service->syncAll();

        return response()->json($result);
    }

    public function syncCategories(): JsonResponse
    {
        $result = $this->service->syncCategories();

        return response()->json($result);
    }

    public function syncProducts(): JsonResponse
    {
        $result = $this->service->syncProducts();

        return response()->json($result);
    }

    public function syncStocks(): JsonResponse
    {
        $result = $this->service->syncStocks();

        return response()->json($result);
    }

    public function exportOrders(): JsonResponse
    {
        $result = $this->service->exportOrders();

        return response()->json($result);
    }
}
