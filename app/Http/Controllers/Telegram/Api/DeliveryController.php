<?php

namespace App\Http\Controllers\Telegram\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryController
{
    private function getCdekToken(): ?string
    {
        return Cache::remember('cdek_token', 600, function () {
            $response = Http::asForm()->post(config('services.cdek.api_url') . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.cdek.client_id'),
                'client_secret' => config('services.cdek.client_secret'),
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            return null;
        });
    }

    public function cdekPvz(Request $request): JsonResponse
    {
        $cityCode = $request->get('city_code');
        $postalCode = $request->get('postal_code');
        $cityName = $request->get('city');
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        $token = $this->getCdekToken();
        if (!$token) {
            return response()->json(['error' => 'CDEK auth failed'], 500);
        }

        // If city name provided, resolve to city_code via CDEK API
        if (!$cityCode && $cityName) {
            $cityCode = $this->resolveCdekCityCode($token, $cityName);
        }

        // If still no city_code but we have coordinates, try reverse lookup via CDEK
        if (!$cityCode && $lat && $lng) {
            $cityCode = $this->resolveCdekCityByCoords($token, (float) $lat, (float) $lng);
        }

        // Build params
        $params = ['type' => 'PVZ'];
        if ($cityCode) {
            $params['city_code'] = $cityCode;
        } elseif ($postalCode) {
            $params['postal_code'] = $postalCode;
        } else {
            return response()->json([]);
        }

        $cacheKey = 'cdek_pvz_' . md5(json_encode($params));

        $pvzList = Cache::remember($cacheKey, 3600, function () use ($token, $params) {
            $response = Http::withToken($token)
                ->get(config('services.cdek.api_url') . '/deliverypoints', $params);

            if (!$response->successful()) return [];

            return collect($response->json())
                ->map(fn($pvz) => [
                    'code' => $pvz['code'] ?? '',
                    'name' => $pvz['name'] ?? '',
                    'address' => $pvz['location']['address_full'] ?? $pvz['location']['address'] ?? '',
                    'city' => $pvz['location']['city'] ?? '',
                    'city_code' => $pvz['location']['city_code'] ?? null,
                    'lat' => $pvz['location']['latitude'] ?? null,
                    'lng' => $pvz['location']['longitude'] ?? null,
                    'work_time' => $pvz['work_time'] ?? '',
                    'phone' => $pvz['phones'][0]['number'] ?? '',
                ])
                ->values()
                ->toArray();
        });

        return response()->json($pvzList);
    }

    private function resolveCdekCityCode(string $token, string $cityName): ?int
    {
        $cacheKey = 'cdek_city_' . md5($cityName);

        return Cache::remember($cacheKey, 86400, function () use ($token, $cityName) {
            $response = Http::withToken($token)
                ->get(config('services.cdek.api_url') . '/location/cities', [
                    'city' => $cityName,
                    'size' => 1,
                ]);

            if ($response->successful()) {
                $cities = $response->json();
                if (!empty($cities) && isset($cities[0]['code'])) {
                    return (int) $cities[0]['code'];
                }
            }

            return null;
        });
    }

    private function resolveCdekCityByCoords(string $token, float $lat, float $lng): ?int
    {
        $cacheKey = 'cdek_city_coords_' . round($lat, 2) . '_' . round($lng, 2);

        return Cache::remember($cacheKey, 86400, function () use ($token, $lat, $lng) {
            // CDEK doesn't support coordinate lookup directly, use Yandex geocoder to get city name
            $apiKey = config('services.yandex.geocoder_key');
            $geoResponse = Http::get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $apiKey,
                'format' => 'json',
                'geocode' => "{$lng},{$lat}",
                'kind' => 'locality',
                'results' => 1,
            ]);

            if ($geoResponse->successful()) {
                $cityName = data_get(
                    $geoResponse->json(),
                    'response.GeoObjectCollection.featureMember.0.GeoObject.name'
                );
                if ($cityName) {
                    return $this->resolveCdekCityCode($token, $cityName);
                }
            }

            return null;
        });
    }

    public function cdekCalculate(Request $request): JsonResponse
    {
        $request->validate([
            'to_city_code' => 'nullable|integer',
            'pvz_code' => 'nullable|string|max:100',
            'weight' => 'nullable|integer|min:50|max:50000',
        ]);

        $token = $this->getCdekToken();
        if (!$token) {
            return response()->json(['error' => 'CDEK auth failed'], 500);
        }

        $fromCityCode = config('services.cdek.from_city_code', 44); // Moscow

        // Если задан pvz_code, но нет to_city_code — пытаемся найти city_code ПВЗ через CDEK API
        $toCityCode = (int) ($request->to_city_code ?? 0);
        if (!$toCityCode && $request->pvz_code) {
            $toCityCode = $this->resolvePvzCityCode($token, (string) $request->pvz_code);
        }

        if (!$toCityCode) {
            return response()->json(['error' => 'no_destination_city'], 400);
        }

        // Вес посылки регулируется через config (по умолчанию 300 г)
        $weight = (int) ($request->weight ?? config('services.cdek.default_weight', 300));

        $response = Http::withToken($token)
            ->post(config('services.cdek.api_url') . '/calculator/tarifflist', [
                'from_location' => ['code' => $fromCityCode],
                'to_location' => ['code' => $toCityCode],
                'packages' => [
                    ['weight' => $weight, 'length' => 20, 'width' => 15, 'height' => 10],
                ],
            ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Calculation failed', 'details' => $response->json()], 500);
        }

        // Тарифы СДЭК для ПВЗ-доставки (по приоритету / приемлемости):
        //   136 — Посылка склад-склад (самый дешёвый, основной для ПВЗ→ПВЗ)
        //   234 — Экономичный экспресс склад-склад
        //   366 — Магистральный экспресс склад-склад
        //   137 — Посылка склад-дверь (на случай если 136 не вернётся)
        //   480 — Экспресс магистральный (резерв)
        $preferredTariffs = [136, 234, 366, 137, 480];

        $allTariffs = collect($response->json('tariff_codes', []))
            ->map(fn($t) => [
                'tariff_code' => (int) ($t['tariff_code'] ?? 0),
                'tariff_name' => $t['tariff_name'] ?? '',
                'price' => $t['delivery_sum'] ?? 0,
                'min_days' => $t['period_min'] ?? 0,
                'max_days' => $t['period_max'] ?? 0,
            ])
            ->filter(fn($t) => $t['price'] > 0)
            ->sortBy('price')
            ->values();

        // Сначала пробуем приоритетные ПВЗ-тарифы
        $tariffs = $allTariffs->filter(
            fn($t) => in_array($t['tariff_code'], $preferredTariffs, true)
        )->values();

        // Если ничего не подошло — берём ВСЕ доступные тарифы и отдаём дешёвые,
        // чтобы клиент хотя бы видел приблизительную цену
        if ($tariffs->isEmpty() && $allTariffs->isNotEmpty()) {
            $tariffs = $allTariffs;
        }

        if ($tariffs->isEmpty()) {
            Log::warning('CDEK calculate: no tariffs returned', [
                'from' => $fromCityCode,
                'to' => $toCityCode,
                'response_size' => count($response->json('tariff_codes', [])),
            ]);
        }

        return response()->json($tariffs);
    }

    /**
     * Определение city_code по коду ПВЗ через CDEK API.
     */
    private function resolvePvzCityCode(string $token, string $pvzCode): ?int
    {
        $cacheKey = 'cdek_pvz_city_' . md5($pvzCode);

        return Cache::remember($cacheKey, 86400, function () use ($token, $pvzCode) {
            try {
                $response = Http::withToken($token)
                    ->get(config('services.cdek.api_url') . '/deliverypoints', ['code' => $pvzCode]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data) && isset($data[0]['location']['city_code'])) {
                        return (int) $data[0]['location']['city_code'];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to resolve PVZ city_code: ' . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Server-side reverse geocode (coords → address).
     * Tries Yandex HTTP Geocoder first, falls back to Nominatim (OSM).
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        if (!$lat || !$lng) {
            return response()->json(['error' => 'lat and lng required'], 400);
        }

        $cacheKey = 'geo_rev_' . round((float)$lat, 5) . '_' . round((float)$lng, 5);

        $result = Cache::remember($cacheKey, 3600, function () use ($lat, $lng) {
            // Try Yandex first
            $yandex = $this->yandexReverseGeocode((float)$lat, (float)$lng);
            if ($yandex) return $yandex;

            // Fallback: Nominatim (OSM)
            return $this->nominatimReverseGeocode((float)$lat, (float)$lng);
        });

        return response()->json($result ?: ['address' => '', 'city' => '']);
    }

    /**
     * Server-side forward geocode (address → coords).
     * Tries Yandex first, falls back to Nominatim.
     */
    public function forwardGeocode(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (mb_strlen($query) < 2) {
            return response()->json(['error' => 'query too short'], 400);
        }

        $cacheKey = 'geo_fwd_' . md5($query);

        $result = Cache::remember($cacheKey, 3600, function () use ($query) {
            // Try Yandex first
            $yandex = $this->yandexForwardGeocode($query);
            if ($yandex) return $yandex;

            // Fallback: Nominatim (OSM)
            return $this->nominatimForwardGeocode($query);
        });

        return response()->json($result ?: ['lat' => 0, 'lng' => 0, 'address' => '', 'city' => '']);
    }

    private function yandexReverseGeocode(float $lat, float $lng): ?array
    {
        $apiKey = config('services.yandex.geocoder_key');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $apiKey,
                'format' => 'json',
                'geocode' => "{$lng},{$lat}",
                'results' => 1,
            ]);

            if (!$response->successful()) return null;

            $geoObject = data_get(
                $response->json(),
                'response.GeoObjectCollection.featureMember.0.GeoObject'
            );
            if (!$geoObject) return null;

            $address = data_get($geoObject, 'metaDataProperty.GeocoderMetaData.text', '');
            $locality = $this->extractYandexLocality($geoObject);

            return $address ? ['address' => $address, 'city' => $locality] : null;
        } catch (\Throwable $e) {
            \Log::warning('Yandex reverse geocode failed: ' . $e->getMessage());
            return null;
        }
    }

    private function yandexForwardGeocode(string $query): ?array
    {
        $apiKey = config('services.yandex.geocoder_key');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $apiKey,
                'format' => 'json',
                'geocode' => $query,
                'results' => 1,
            ]);

            if (!$response->successful()) return null;

            $geoObject = data_get(
                $response->json(),
                'response.GeoObjectCollection.featureMember.0.GeoObject'
            );
            if (!$geoObject) return null;

            $pos = data_get($geoObject, 'Point.pos', '');
            $parts = explode(' ', $pos);
            $lng = (float) ($parts[0] ?? 0);
            $lat = (float) ($parts[1] ?? 0);
            $address = data_get($geoObject, 'metaDataProperty.GeocoderMetaData.text', '');
            $locality = $this->extractYandexLocality($geoObject);

            return ($lat && $lng) ? ['lat' => $lat, 'lng' => $lng, 'address' => $address, 'city' => $locality] : null;
        } catch (\Throwable $e) {
            \Log::warning('Yandex forward geocode failed: ' . $e->getMessage());
            return null;
        }
    }

    private function extractYandexLocality(array $geoObject): string
    {
        $components = data_get($geoObject, 'metaDataProperty.GeocoderMetaData.Address.Components', []);
        foreach ($components as $c) {
            if (($c['kind'] ?? '') === 'locality') return $c['name'] ?? '';
        }
        return '';
    }

    private function nominatimReverseGeocode(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'AlyansDistributions/1.0', 'Accept-Language' => 'ru'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (!$response->successful()) return null;

            $data = $response->json();
            $address = $data['display_name'] ?? '';
            $city = $data['address']['city']
                ?? $data['address']['town']
                ?? $data['address']['village']
                ?? $data['address']['state']
                ?? '';

            return $address ? ['address' => $address, 'city' => $city] : null;
        } catch (\Throwable $e) {
            \Log::warning('Nominatim reverse geocode failed: ' . $e->getMessage());
            return null;
        }
    }

    private function nominatimForwardGeocode(string $query): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'AlyansDistributions/1.0', 'Accept-Language' => 'ru'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'countrycodes' => 'ru',
                ]);

            if (!$response->successful()) return null;

            $results = $response->json();
            if (empty($results)) return null;

            $first = $results[0];
            $lat = (float) ($first['lat'] ?? 0);
            $lng = (float) ($first['lon'] ?? 0);
            $address = $first['display_name'] ?? '';
            $city = $first['address']['city']
                ?? $first['address']['town']
                ?? $first['address']['village']
                ?? $first['address']['state']
                ?? '';

            return ($lat && $lng) ? ['lat' => $lat, 'lng' => $lng, 'address' => $address, 'city' => $city] : null;
        } catch (\Throwable $e) {
            \Log::warning('Nominatim forward geocode failed: ' . $e->getMessage());
            return null;
        }
    }

    public function yandexPvz(Request $request): JsonResponse
    {
        $address = trim((string) $request->get('address', ''));
        $city = trim((string) $request->get('city', ''));
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        if (!$address && !$city && (!$lat || !$lng)) {
            return response()->json(['error' => 'address, city or lat/lng required'], 400);
        }

        $apiKey = config('services.yandex.geocoder_key');

        // Геокодируем город или адрес в координаты если нужно
        if ((!$lat || !$lng) && ($city || $address)) {
            $query = $city ?: $address;
            $geo = $this->yandexForwardGeocode($query);
            if (!$geo && $query) {
                $geo = $this->nominatimForwardGeocode($query);
            }

            if ($geo) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        if (!$lat || !$lng) {
            return response()->json(['pvz' => [], 'geocoded' => ['lat' => 0, 'lng' => 0]]);
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        $token = config('services.yandex.delivery_token');

        if (!$token) {
            \Log::error('Yandex PVZ: YANDEX_DELIVERY_TOKEN is empty');
            return response()->json(['pvz' => [], 'geocoded' => ['lat' => $lat, 'lng' => $lng], 'debug' => 'no token']);
        }

        // Пробуем получить ПВЗ — без кэша для отладки
        $pvzList = [];
        $debugInfo = [];

        try {
            $maxBodySize = 30 * 1024 * 1024; // allow up to 30 MB of JSON from Yandex
            $searchAreas = [];
            $points = [];
            $debugInfo['attempts'] = [];
            $responseJson = null;
            $requestBody = [];

            if ($city && $apiKey) {
                try {
                    $cityResponse = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                        'apikey' => $apiKey,
                        'format' => 'json',
                        'geocode' => $city,
                        'results' => 1,
                        'kind' => 'locality',
                    ]);

                    if ($cityResponse->successful()) {
                        $envelope = data_get($cityResponse->json(), 'response.GeoObjectCollection.featureMember.0.GeoObject.boundedBy.Envelope');
                        $lower = explode(' ', data_get($envelope, 'lowerCorner', ''));
                        $upper = explode(' ', data_get($envelope, 'upperCorner', ''));
                        if (count($lower) === 2 && count($upper) === 2) {
                            $searchAreas[] = [
                                'latitude' => [
                                    'from' => (float) $lower[1],
                                    'to' => (float) $upper[1],
                                ],
                                'longitude' => [
                                    'from' => (float) $lower[0],
                                    'to' => (float) $upper[0],
                                ],
                                'description' => 'city_bounds',
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Yandex PVZ city geocode failed: ' . $e->getMessage());
                }
            }

            $deltas = [0.3, 0.6, 1.0]; // пробуем сначала около 30 км, затем расширяем область
            foreach ($deltas as $delta) {
                $searchAreas[] = [
                    'latitude' => [
                        'from' => round($lat - $delta, 6),
                        'to' => round($lat + $delta, 6),
                    ],
                    'longitude' => [
                        'from' => round($lng - $delta, 6),
                        'to' => round($lng + $delta, 6),
                    ],
                    'description' => 'delta_' . $delta,
                ];
            }

            foreach ($searchAreas as $area) {
                $offset = 0;
                $page = 0;
                $pageLimit = 100;
                $maxPages = 3;

                while ($page < $maxPages) {
                    $requestBody = [
                        'latitude' => $area['latitude'],
                        'longitude' => $area['longitude'],
                        'limit' => $pageLimit,
                        'offset' => $offset,
                    ];

                    $response = Http::withToken($token)
                        ->withHeaders([
                            'Accept-Language' => 'ru',
                            'Accept' => 'application/json',
                        ])
                        ->timeout(15)
                        ->post('https://b2b-authproxy.taxi.yandex.net/api/b2b/platform/pickup-points/list', $requestBody);

                    $responseContentType = $response->header('Content-Type', '');
                    $raw = $response->body();
                    $bodySize = strlen($raw);
                    $attempt = [
                        'area' => $area['description'] ?? 'unknown',
                        'page' => $page,
                        'offset' => $offset,
                        'limit' => $pageLimit,
                        'status' => $response->status(),
                        'content_type' => $responseContentType,
                        'body_size' => $bodySize,
                    ];

                    if ($response->successful()) {
                        $trimmed = ltrim($raw);
                        if ($bodySize <= $maxBodySize && stripos($trimmed, '<!DOCTYPE') === false) {
                            $decoded = json_decode($raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $responseJson = $decoded;
                                $candidate = data_get($decoded, 'points', []);
                                $attempt['points'] = is_array($candidate) ? count($candidate) : 0;
                                if (is_array($candidate) && count($candidate) > 0) {
                                    $points = array_merge($points, $candidate);
                                    if (count($candidate) < $pageLimit || count($points) >= 300) {
                                        break;
                                    }
                                    $offset += $pageLimit;
                                    $page++;
                                    $debugInfo['attempts'][] = $attempt;
                                    continue;
                                }
                            } else {
                                $attempt['json_error'] = json_last_error_msg();
                            }
                        } else {
                            $attempt['body_error'] = $bodySize > $maxBodySize ? 'body too large' : 'invalid HTML response';
                        }
                    } else {
                        $attempt['body_snippet'] = mb_substr($raw, 0, 500);
                    }

                    $debugInfo['attempts'][] = $attempt;
                    break;
                }

                if (!empty($points)) {
                    break;
                }
            }

            if (empty($points)) {
                \Log::warning('Yandex PVZ API returned empty points in all attempts', [
                    'request' => $requestBody,
                    'attempts' => $debugInfo['attempts'],
                ]);
            }

            $debugInfo['points_count'] = count($points);

            $pvzList = collect($points)->unique('id')->map(fn($pvz) => [
                'id' => $pvz['id'] ?? '',
                'name' => $pvz['name'] ?? '',
                'address' => data_get($pvz, 'address.full_address')
                    ?? data_get($pvz, 'address.address')
                    ?? data_get($pvz, 'address.string')
                    ?? '',
                'lat' => data_get($pvz, 'position.latitude'),
                'lng' => data_get($pvz, 'position.longitude'),
                'instruction' => $pvz['instruction'] ?? '',
                'phone' => data_get($pvz, 'contact.phone', ''),
                'payment_methods' => $pvz['payment_methods'] ?? [],
                'is_yandex_branded' => $pvz['is_yandex_branded'] ?? false,
            ])->values()->toArray();
        } catch (\Throwable $e) {
            \Log::error('Yandex PVZ exception: ' . $e->getMessage());
            $debugInfo['exception'] = $e->getMessage();
        }

        return response()->json([
            'pvz' => $pvzList,
            'geocoded' => ['lat' => $lat, 'lng' => $lng],
            '_debug' => $debugInfo,
        ]);
    }
}
