# DI Services

The bundle registers three public services you can inject anywhere in your Symfony application.

## Available services

| Service ID | Class | Description |
|---|---|---|
| `algebra.factory` | `CollectionFactory` | Creates `RelationalCollection` from any supported input |
| `algebra.evaluator` | `ExpressionEvaluator` | Evaluates string expressions and closures |
| `algebra.aggregates` | `AggregateRegistry` | Registry of all aggregate functions |

## Injecting `CollectionFactory`

The primary service for most use cases. It includes all tagged adapters
(Doctrine, custom adapters registered via `#[AsAlgebraAdapter]`).

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
            ->aggregate(['revenue' => 'sum(amount)', 'count' => 'count(*)'])
            ->orderBy('revenue', 'desc')
            ->toArray();

        return new JsonResponse($result);
    }
}
```

## Using `Algebra::from()` statically

`Algebra::from()` continues to work as documented. The bundle's
`AlgebraBootstrapListener` calls `Algebra::reset()` on the first request and
re-registers all tagged aggregates, so your custom aggregate functions are
available when calling `Algebra::from()`.

```php
use Nalabdou\Algebra\Algebra;

// Works — custom aggregates registered via #[AsAggregate] are available
$result = Algebra::from($orders)
    ->aggregate(['geo' => 'geomean(price)'])
    ->toArray();
```

> **Custom adapters and `Algebra::from()`:**
> Custom adapters registered via `#[AsAlgebraAdapter]` are only available
> through the injectable `algebra.factory` service.
> `Algebra::from()` uses its own internal factory which only supports
> the built-in array, generator, and Traversable adapters.

## Injecting `ExpressionEvaluator`

Useful if you need to evaluate expressions outside of a pipeline:

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

## Injecting `AggregateRegistry`

Useful for programmatic aggregate registration or inspection:

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

## Autowiring

All three services support autowiring by type:

```php
// Any of these type hints works in any Symfony service constructor
private readonly CollectionFactory $algebraFactory
private readonly ExpressionEvaluator $evaluator
private readonly AggregateRegistry $aggregates
```
