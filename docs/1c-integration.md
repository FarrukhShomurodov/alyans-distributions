# 1C Integration API

Base path: `/api/integrations/1c`

Authentication:
- Send token in header `X-1C-Token: <ONEC_TOKEN>`
- Alternative: `Authorization: Bearer <ONEC_TOKEN>`

## 1. Health check
`GET /health` — публичный, аутентификация не требуется

Response:
```json
{
  "ok": true,
  "service": "1c-integration"
}
```

## 2. Export orders to 1C
`GET /orders?status=new&only_not_exported=1&limit=100`

Query params:
- `status` (optional): `new|in_process|done|canceled`
- `only_not_exported` (optional, default `1`)
- `limit` (optional, default `100`, max `500`)

Response contains order info, customer, and lines with `product_external_id` when available.

## 3. Mark orders as exported
`POST /orders/mark-exported`

Request:
```json
{
  "order_ids": [101, 102, 103]
}
```

## 4. Update order status from 1C
`PATCH /orders/{order}/status`

Request:
```json
{
  "status": "in_process"
}
```

Allowed statuses:
- `new`
- `in_process`
- `done`
- `canceled`

## 5. Sync stock balances from 1C
`POST /stocks/sync`

Request by external id:
```json
{
  "items": [
    {"external_id": "1C-0001", "quantity": 15},
    {"external_id": "1C-0002", "quantity": 0}
  ]
}
```

Request by product id:
```json
{
  "items": [
    {"product_id": 10, "quantity": 3},
    {"product_id": 11, "quantity": 7}
  ]
}
```

Response:
```json
{
  "updated": 2,
  "skipped": 0,
  "errors": []
}
```

## Data model changes
Run migrations:
- adds `products.external_id` for 1C product mapping
- adds `orders.one_c_exported_at` for incremental export
