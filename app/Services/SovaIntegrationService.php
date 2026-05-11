<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SovaIntegrationService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sova.base_url'), '/');
        $this->token = (string) config('services.sova.token');
    }

    public function syncAll(): array
    {
        $categories = $this->syncCategories();
        $products = $this->syncProducts();
        $stocks = $this->syncStocks();

        return [
            'categories' => $categories,
            'products' => $products,
            'stocks' => $stocks,
        ];
    }

    public function syncCategories(): array
    {
        $data = $this->fetch('/categories');

        if ($data === null) {
            return ['error' => 'Failed to fetch categories from Sova'];
        }

        $items = $this->extractItems($data);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $parentsLinked = 0;

        DB::transaction(function () use ($items, &$created, &$updated, &$skipped, &$parentsLinked) {
            // === Проход 1: создаём/обновляем все категории БЕЗ parent_id ===
            foreach ($items as $item) {
                $externalId = $this->getExternalId($item);

                if (!$externalId) {
                    $skipped++;
                    continue;
                }

                $name = $item['name'] ?? $item['title'] ?? null;

                if (!$name) {
                    $skipped++;
                    continue;
                }

                $categoryData = [
                    'name' => $name,
                    'description' => $item['description'] ?? '',
                    'is_active' => $item['is_active'] ?? $item['active'] ?? true,
                    'photo_url' => $item['photo_url'] ?? $item['image'] ?? $item['photo'] ?? null,
                ];

                $existing = Category::where('external_id', (string) $externalId)->first();

                if ($existing) {
                    $existing->update($categoryData);
                    $updated++;
                } else {
                    $categoryData['external_id'] = (string) $externalId;
                    Category::create($categoryData);
                    $created++;
                }
            }

            // === Проход 2: проставляем parent_id по parentId из Совы ===
            foreach ($items as $item) {
                $externalId = $this->getExternalId($item);
                if (!$externalId) {
                    continue;
                }

                // Сова отправляет parentId (camelCase)
                $parentExtId = $item['parentId'] ?? $item['parent_id'] ?? $item['parent_external_id'] ?? null;

                $category = Category::where('external_id', (string) $externalId)->first();
                if (!$category) {
                    continue;
                }

                if ($parentExtId === null) {
                    // Корневая категория — убираем parent если был
                    if ($category->parent_id !== null) {
                        $category->update(['parent_id' => null]);
                    }
                    continue;
                }

                $parent = Category::where('external_id', (string) $parentExtId)->first();
                if ($parent && $category->parent_id !== $parent->id) {
                    $category->update(['parent_id' => $parent->id]);
                    $parentsLinked++;
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'parentsLinked');
    }

    public function syncProducts(): array
    {
        $data = $this->fetch('/products');

        if ($data === null) {
            return ['error' => 'Failed to fetch products from Sova'];
        }

        $items = $this->extractItems($data);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($items, &$created, &$updated, &$skipped, &$errors) {
            foreach ($items as $index => $item) {
                $externalId = $this->getExternalId($item);

                if (!$externalId) {
                    $skipped++;
                    continue;
                }

                $name = $item['name'] ?? $item['title'] ?? null;

                // Пропускаем товары без имени или с именем "-"
                if (!$name || trim($name) === '-' || trim($name) === '') {
                    $skipped++;
                    continue;
                }

                // Сова отдаёт category_ids (число, не массив)
                $categoryExtId = $item['category_ids'] ?? $item['categoryId'] ?? $item['category_id'] ?? null;
                $category = null;

                // category_ids = 0 означает "без категории" — пропускаем
                if ($categoryExtId && (int) $categoryExtId > 0) {
                    $category = Category::where('external_id', (string) $categoryExtId)->first();
                }

                if (!$category) {
                    $skipped++;
                    $errors[] = [
                        'index' => $index,
                        'external_id' => $externalId,
                        'name' => $name,
                        'reason' => 'Category not found for category_ids: ' . ($categoryExtId ?? 'null'),
                    ];
                    continue;
                }

                $productData = [
                    'name' => $name,
                    'category_id' => $category->id,
                    'price' => (float) ($item['price'] ?? 0),
                    'discount_percent' => $item['discount_percent'] ?? $item['discount'] ?? 0,
                    'is_active' => $item['is_active'] ?? $item['active'] ?? true,
                ];

                $existing = Product::where('external_id', (string) $externalId)->first();

                if ($existing) {
                    $existing->update($productData);
                    $updated++;
                } else {
                    $productData['external_id'] = (string) $externalId;
                    Product::create($productData);
                    $created++;
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public function syncStocks(): array
    {
        $data = $this->fetch('/stocks');

        if ($data === null) {
            return ['error' => 'Failed to fetch stocks from Sova'];
        }

        $items = $this->extractItems($data);
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($items, &$updated, &$skipped, &$errors) {
            foreach ($items as $index => $item) {
                // Сова отдаёт: {"id": 58521, "quantity": 17861.0}
                // id — это external_id продукта, quantity — float
                $externalId = $item['id'] ?? $item['product_id'] ?? $item['external_id'] ?? null;
                $quantity = $item['quantity'] ?? $item['qty'] ?? $item['stock'] ?? null;

                if ($quantity === null || !is_numeric($quantity)) {
                    $skipped++;
                    $errors[] = ['index' => $index, 'reason' => 'Invalid quantity'];
                    continue;
                }

                if (!$externalId) {
                    $skipped++;
                    $errors[] = ['index' => $index, 'reason' => 'No product identifier'];
                    continue;
                }

                $product = Product::where('external_id', (string) $externalId)->first();

                if (!$product) {
                    $skipped++;
                    $errors[] = ['index' => $index, 'reason' => "Product not found: $externalId"];
                    continue;
                }

                $stock = $product->stock()->firstOrCreate([], ['quantity' => 0]);
                $oldQuantity = (int) $stock->quantity;
                $newQuantity = (int) $quantity;

                if ($oldQuantity === $newQuantity) {
                    $skipped++;
                    continue;
                }

                $difference = $newQuantity - $oldQuantity;

                $stock->history()->create([
                    'type' => $difference >= 0 ? 'plus' : 'minus',
                    'quantity' => abs($difference),
                    'difference' => $difference,
                    'previous_quantity' => $oldQuantity,
                    'updated_by' => null,
                    'source' => 'sova',
                ]);

                $stock->update(['quantity' => $newQuantity]);
                $updated++;
            }
        });

        return compact('updated', 'skipped', 'errors');
    }

    /**
     * Экспорт новых заказов в Сову.
     *
     * Отправка ПО ОДНОМУ заказу с ожиданием ответа:
     * - пока Сова не ответит успехом, к следующему не переходим
     * - если заказ получил ошибку — логируем и прерываем цикл (повторим через 30 минут)
     * - заказы с товарами без external_id ПРОПУСКАЮТСЯ
     */
    public function exportOrders(bool $onlyNew = true, int $limit = 100): array
    {
        $query = Order::query()
            ->with(['user', 'items.product'])
            ->when($onlyNew, fn($q) => $q->whereNull('sova_exported_at'))
            ->orderBy('id')
            ->limit($limit);

        $allOrders = $query->get();

        if ($allOrders->isEmpty()) {
            return ['exported' => 0, 'message' => 'No new orders to export'];
        }

        $exportedIds = [];
        $skipped = [];
        $failed = [];
        $lastResponse = null;

        foreach ($allOrders as $order) {
            // Проверяем, что все товары в заказе имеют external_id
            $invalidItems = $order->items->filter(fn($item) => empty($item->product?->external_id));

            if ($invalidItems->isNotEmpty()) {
                $reason = 'Товары без external_id: ' .
                    $invalidItems->map(fn($i) => $i->product?->name ?? "#{$i->product_id}")->implode(', ');
                $skipped[] = ['order_id' => $order->id, 'reason' => $reason];

                Log::warning("Sova export: order #{$order->id} skipped — items without external_id", [
                    'order_id' => $order->id,
                    'items' => $invalidItems->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'name' => $i->product?->name,
                    ])->values()->all(),
                ]);
                continue;
            }

            // Формируем payload для ОДНОГО заказа
            $payload = [
                'source' => 'tg',
                'orders' => [$this->buildOrderPayload($order)],
            ];

            // Отправляем в Сову и ждём ответ
            $response = $this->postToSova('/order', $payload);

            // Сеть/HTTP-ошибка — ПРЕРЫВАЕМ цикл (Сова недоступна, бессмысленно
            // дальше долбить её другими заказами — повторим всё через 30 минут)
            if ($response === null) {
                $failed[] = [
                    'order_id' => $order->id,
                    'reason' => 'Нет ответа от Совы (сеть/5xx)',
                ];
                Log::warning("Sova export: order #{$order->id} — нет ответа, прерываем цикл");
                break;
            }

            // Сова ответила ошибкой по конкретному заказу — это проблема ИМЕННО этого заказа
            // (плохие данные, дубль и т.п.). Идём к следующему, этот остаётся в очереди
            // и будет повторён на следующем запуске (когда админ поправит данные).
            if ($this->isSovaError($response)) {
                $failed[] = [
                    'order_id' => $order->id,
                    'reason' => $this->extractSovaError($response),
                    'sova_response' => $response,
                ];
                Log::warning("Sova export: order #{$order->id} — Сова вернула ошибку, пропускаем", [
                    'response' => $response,
                ]);
                continue;
            }

            // Успех — помечаем заказ
            $order->update(['sova_exported_at' => Carbon::now()]);
            $exportedIds[] = $order->id;
            $lastResponse = $response;

            Log::info("Sova export: order #{$order->id} успешно отправлен", [
                'response' => $response,
            ]);
        }

        return [
            'exported' => count($exportedIds),
            'order_ids' => $exportedIds,
            'skipped' => $skipped,
            'failed' => $failed,
            'sova_response' => $lastResponse,
        ];
    }

    /**
     * Построение payload одного заказа для Совы.
     */
    private function buildOrderPayload(Order $order): array
    {
        $customer = $order->user;
        $fullName = trim(
            ($order->first_name ?? $customer?->first_name ?? '') . ' ' .
            ($order->last_name ?? $customer?->second_name ?? '')
        );

        return [
            'order_no' => (string) $order->id,
            'order_id' => (string) $order->id,
            'order_date' => optional($order->created_at)?->format('Y-m-d'),
            'order_delivery_date' => $order->delivery_date ?? optional($order->created_at)?->format('Y-m-d'),
            'order_delivery_type' => $order->delivery_method ?? $order->delivery_type ?? '',
            'order_delivery_address' => $order->delivery_address ?? '',
            'order_delivery_price' => (float) ($order->delivery_price ?? 0),
            'order_customer' => $fullName ?: 'Покупатель',
            'order_customer_mail' => $order->email ?? '',
            'order_status' => $order->status_name,
            'order_customer_phone' => $order->phone ?? $order->delivery_phone ?? $customer?->phone ?? '',
            'order_comment' => $order->comment ?? '',
            'order_content' => $order->items->map(fn($item) => [
                'nmatr_id' => (int) $item->product->external_id,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ])->values()->all(),
        ];
    }

    /**
     * Проверяем ответ Совы — есть ли в нём признак ошибки.
     *
     * Ожидаемые форматы ответа при ошибке (см. документацию):
     *   { "status": "error", "message": "..." }
     *   { "success": false, "error": "..." }
     *   { "errors": [ {"order_id": "55", "message": "..."} ] }
     *   { "result": "error", "description": "..." }
     */
    private function isSovaError(array $response): bool
    {
        // status: error / fail
        $status = strtolower((string) Arr::get($response, 'status', ''));
        if (in_array($status, ['error', 'fail', 'failed'], true)) {
            return true;
        }

        // success: false
        if (array_key_exists('success', $response) && $response['success'] === false) {
            return true;
        }

        // result: error
        $result = strtolower((string) Arr::get($response, 'result', ''));
        if (in_array($result, ['error', 'fail', 'failed'], true)) {
            return true;
        }

        // errors: [...]
        $errors = Arr::get($response, 'errors');
        if (is_array($errors) && count($errors) > 0) {
            return true;
        }

        // error: "..." (непустая строка)
        $error = Arr::get($response, 'error');
        if (is_string($error) && trim($error) !== '') {
            return true;
        }

        return false;
    }

    /**
     * Извлекаем человекочитаемый текст ошибки из ответа Совы.
     */
    private function extractSovaError(array $response): string
    {
        return (string) (
            Arr::get($response, 'message')
            ?? Arr::get($response, 'error')
            ?? Arr::get($response, 'description')
            ?? Arr::get($response, 'errors.0.message')
            ?? 'Неизвестная ошибка Совы'
        );
    }

    private function postToSova(string $endpoint, array $data): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post($this->baseUrl . $endpoint, $data);

            if (!$response->successful()) {
                Log::error("Sova API POST error: {$endpoint}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload_count' => count($data['orders'] ?? []),
                ]);
                return null;
            }

            return $response->json() ?? ['status' => 'ok'];
        } catch (\Exception $e) {
            Log::error("Sova API POST exception: {$endpoint}", [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function fetch(string $endpoint): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                Log::error("Sova API error: {$endpoint}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Sova API exception: {$endpoint}", [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function extractItems(array $data): array
    {
        // Убираем отладочные ключи (начинаются с _)
        $filtered = array_filter($data, fn($key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);

        // Если есть ключ data/items/results — берём массив оттуда
        foreach (['data', 'items', 'results', 'categories', 'products', 'stocks'] as $key) {
            if (isset($filtered[$key]) && is_array($filtered[$key])) {
                return $filtered[$key];
            }
        }

        // Если сам ответ — массив элементов (проверяем первый элемент)
        if (isset($filtered[0]) && is_array($filtered[0])) {
            return $filtered;
        }

        return [];
    }

    private function getExternalId(array $item): ?string
    {
        $id = $item['id'] ?? $item['external_id'] ?? null;

        return $id !== null ? (string) $id : null;
    }
}
