# init/commerce-order

Commerce order foundation package with immutable snapshots and cart checkout.

## Что реализовано

- `commerce_orders` и `commerce_order_items` с immutable line snapshots.
- status enum заказа: `draft | placed | cancelled`.
- `BuildOrderSnapshot` и `PlaceOrderFromCart`.
- генератор номера заказа `ORD-YYYYMMDD-XXXXXX`.
- checkout API: `POST /api/commerce/order/v1/checkout`.
- cart conversion: active cart помечается `converted`, заполняются `converted_order_id` и `checked_out_at`.
- мягкий вызов stock allocation через `Init\Commerce\Stock\Actions\AllocateStockForOrder`, если есть tracked items; allocation работает как reservation на default warehouse.
- Filament resource `Заказы` с relation manager для order items.

## Установка

```bash
composer require init/commerce-order
```

## Использование

- checkout требует authenticated user или `X-Visitor-Session`.
- можно передать `cart_id` и опциональный `customer_snapshot`.
- если `cart_id` не передан, используется active cart текущего actor.

## Структура

- path: `commerce-foundation/commerce-order`
- actions:
- `PlaceOrderFromCart`
- `BuildOrderSnapshot`

## Разработка

- Demo seeders регистрируются только вне production.
- package tests запускаются через `make setup && make test`.
- package checks лежат в `tests/Feature/`.
- app-level Filament integration checks добавлены в `laravel/tests/Feature/Commerce/CommerceAdminIntegrationTest.php`.
