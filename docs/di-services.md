# DI Services

The bundle registers several public services that you can inject anywhere in your Symfony application.

## Available services

| Service ID                 | Class                 | Description                                                       |
| -------------------------- | --------------------- | ----------------------------------------------------------------- |
| `algebra.factory`          | `CollectionFactory`   | Creates `RelationalCollection` instances from any supported input |
| `algebra.evaluator`        | `ExpressionEvaluator` | Evaluates string expressions and closures                         |
| `algebra.aggregates`       | `AggregateRegistry`   | Registry containing all aggregate functions                       |
| `algebra.adapter_registry` | `AdapterRegistry`     | Registry containing all data adapters                             |

---

# Injecting `CollectionFactory`

The primary service for most use cases. It automatically includes all tagged adapters
(Doctrine, custom adapters registered via `#[AsAlgebraAdapter]`, etc.).

```php
use Nalabdou\Algebra\Collection\CollectionFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController
{
    public function __construct(
        private readonly CollectionFactory $algebraFactory,
    ) {}

    #[Route('/api/orders/summary')]
    public function summary(): JsonResponse
    {
        $orders = $this->getOrders(); // array, Doctrine QB, Collection, CSV, ...

        $result = $this->algebraFactory->create($orders)
            ->where("item['status'] == 'paid'")
            ->groupBy('region')
            ->aggregate([
                'revenue' => 'sum(amount)',
                'count' => 'count(*)',
            ])
            ->orderBy('revenue', 'desc')
            ->toArray();

        return new JsonResponse($result);
    }
}
```

---

# Using `Algebra::from()` statically

`Algebra::from()` continues to work as documented.

The bundle registers an `AlgebraBootstrapListener` that calls `Algebra::reset()` on
the first request and re-registers all tagged aggregates and adapters.

This means that **custom aggregate functions and adapters registered via Symfony
tags or attributes are automatically available when using `Algebra::from()`**.

```php
use Nalabdou\Algebra\Algebra;

// Custom aggregates registered via #[AsAggregate] are available
$result = Algebra::from($orders)
    ->aggregate([
        'geo' => 'geomean(price)',
    ])
    ->toArray();
```

---

# Injecting `ExpressionEvaluator`

Useful when you want to evaluate expressions **outside of a relational pipeline**.

```php
use Nalabdou\Algebra\Expression\ExpressionEvaluator;

final class FilterService
{
    public function __construct(
        private readonly ExpressionEvaluator $evaluator,
    ) {}

    public function matches(array $row, string $expression): bool
    {
        return $this->evaluator->evaluate($row, $expression);
    }

    public function resolve(array $row, string $expression): mixed
    {
        return $this->evaluator->resolve($row, $expression);
    }
}
```

---

# Injecting `AggregateRegistry`

Useful for **programmatic aggregate registration or inspection**.

```php
use Nalabdou\Algebra\Aggregate\AggregateRegistry;

final class AggregateInspector
{
    public function __construct(
        private readonly AggregateRegistry $aggregates,
    ) {}

    public function available(): array
    {
        return array_keys($this->aggregates->all());
    }

    public function hasGeomean(): bool
    {
        return $this->aggregates->has('geomean');
    }
}
```

---

# Injecting `AdapterRegistry`

The `AdapterRegistry` stores all registered adapters and allows you to inspect
or manually register adapters at runtime.

```php
use Nalabdou\Algebra\Adapter\AdapterRegistry;

final class AdapterInspector
{
    public function __construct(
        private readonly AdapterRegistry $adapters,
    ) {}

    public function available(): array
    {
        return array_keys($this->adapters->all());
    }

    public function hasDoctrineAdapter(): bool
    {
        return $this->adapters->has('doctrine');
    }
}
```

---

# Autowiring

All services support autowiring by type:

```php
private readonly CollectionFactory $algebraFactory
private readonly ExpressionEvaluator $evaluator
private readonly AggregateRegistry $aggregates
private readonly AdapterRegistry $adapters
```

Symfony will automatically inject the correct service based on the type hint.
