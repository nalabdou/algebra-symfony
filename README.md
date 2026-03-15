# algebra-symfony

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-black)](https://symfony.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Symfony 7 bundle for [algebra-php](https://github.com/nalabdou/algebra-php) — the pure PHP relational algebra engine.

> **No changes to algebra-php are required.** This bundle works entirely with
> its public API.

---

## Installation

```bash
composer require nalabdou/algebra-symfony
```

Symfony Flex registers the bundle automatically. Without Flex:

```php
// config/bundles.php
return [
    Nalabdou\Algebra\Symfony\AlgebraBundle::class => ['all' => true],
];
```

---

## What the bundle provides

| Feature | Details |
|---|---|
| DI services | `algebra.factory`, `algebra.evaluator`, `algebra.aggregates` |
| Custom aggregates | `#[AsAggregate]` attribute — zero config, auto-registration |
| Custom adapters | `#[AsAlgebraAdapter(priority: N)]` — auto-injected into CollectionFactory |
| Doctrine adapters | Auto-detected when `doctrine/orm` / `doctrine/collections` installed |

> **Twig filters** (`|algebra_where`, `|algebra_orderby`, ...) are provided
> by the separate `nalabdou/algebra-twig` [*Comming soon*] package.

---

## Quick start

### `Algebra::from()` — static, works as documented

```php
use Nalabdou\Algebra\Algebra;

$result = Algebra::from($orders)
    ->where("item['status'] == 'paid'")
    ->groupBy('region')
    ->aggregate(['revenue' => 'sum(amount)', 'orders' => 'count(*)'])
    ->orderBy('revenue', 'desc')
    ->toArray();
```

Custom aggregates registered via `#[AsAggregate]` are available here after the
first request (the `AlgebraBootstrapListener` re-registers them into Algebra's
singleton registry after calling `Algebra::reset()`).

### Injectable `CollectionFactory` — for custom adapters

```php
use Nalabdou\Algebra\Collection\CollectionFactory;

public function __construct(
    private readonly CollectionFactory $algebraFactory,
) {}

public function action(): void
{
    // CollectionFactory has all tagged adapters (Doctrine, CSV, etc.)
    $result = $this->algebraFactory->create($queryBuilder)
        ->where("item['status'] == 'paid'")
        ->toArray();
}
```

---

## Custom aggregates

```php
// src/Aggregate/GeomeanAggregate.php
use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;

#[AsAggregate]
final class GeomeanAggregate implements AggregateInterface
{
    public function name(): string { return 'geomean'; }

    public function compute(array $values): mixed
    {
        return empty($values) ? null : array_product($values) ** (1 / count($values));
    }
}
```

That's it — no `services.yaml`, no `Algebra::aggregates()->register()`. The bundle
discovers it via `#[AsAggregate]` and auto-registers it.

```php
// Now available everywhere
Algebra::from($products)
    ->groupBy('category')
    ->aggregate(['geoMeanPrice' => 'geomean(price)'])
    ->toArray();
```

---

## Custom adapters

```php
// src/Adapter/CsvFileAdapter.php
use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;

#[AsAlgebraAdapter(priority: 50)]
final class CsvFileAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return is_string($input) && str_ends_with($input, '.csv');
    }

    public function toArray(mixed $input): array { /* read CSV */ }
}
```

The adapter is auto-injected into the `algebra.factory` service. Use via injection:

```php
// Via injected CollectionFactory — has all tagged adapters
$result = $this->algebraFactory->create('/data/orders.csv')
    ->where("item['status'] == 'paid'")
    ->toArray();
```

> **Note:** `Algebra::from()` uses its own internal factory which only has the
> built-in array/generator/traversable adapters. Custom adapters require the
> injectable `CollectionFactory`.

---

## Doctrine adapters

Automatically registered when the relevant packages are installed:

| Package | Input type accepted |
|---|---|
| `doctrine/collections` | `ArrayCollection`, `PersistentCollection` |
| `doctrine/orm` | `QueryBuilder` |

```bash
composer require doctrine/orm
composer require doctrine/collections
```

```php
// Doctrine QueryBuilder
$result = $this->algebraFactory->create(
    $em->createQueryBuilder()->select('o')->from(Order::class, 'o')
)
->where("item['status'] == 'paid'")
->toArray();

// Doctrine Collection (e.g. from a OneToMany relation)
$result = $this->algebraFactory->create($user->getOrders())
    ->orderBy('amount', 'desc')
    ->toArray();
```

---

## Configuration

```yaml
# config/packages/algebra.yaml
algebra:
    strict_mode: true   # throw on invalid expressions (default: true)
```

---

## Injectable services

| Service ID | Class | Public |
|---|---|---|
| `algebra.factory` | `CollectionFactory` | ✓ |
| `algebra.evaluator` | `ExpressionEvaluator` | ✓ |
| `algebra.aggregates` | `AggregateRegistry` | ✓ |

---

## Requirements

| | Version |
|---|---|
| PHP | ≥ 8.2 |
| nalabdou/algebra-php | ^1.0 |
| symfony/framework-bundle | ^7.0 |

**Optional:** `doctrine/orm ^3.0`, `doctrine/collections ^2.0`
