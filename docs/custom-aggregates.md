# Custom Aggregates

Register custom aggregate functions with a single PHP attribute.

## Using `#[AsAggregate]`

Implement `AggregateInterface` and add `#[AsAggregate]`:

```php
// src/Aggregate/GeomeanAggregate.php
namespace App\Aggregate;

use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;

#[AsAggregate]
final class GeomeanAggregate implements AggregateInterface
{
    public function name(): string
    {
        return 'geomean';
    }

    public function compute(array $values): mixed
    {
        if (empty($values)) {
            return null;
        }

        return \array_product(\array_map('\abs', $values)) ** (1 / \count($values));
    }
}
```

That's it. No `services.yaml`, no `Algebra::aggregates()->register()`.
The `AggregatePass` compiler pass discovers all tagged services and registers them.

The function is then available everywhere:

```php
// Via Algebra::from() — available after first request
Algebra::from($products)
    ->groupBy('category')
    ->aggregate(['geoMeanPrice' => 'geomean(price)'])
    ->toArray();

// Via injectable CollectionFactory — always available
$this->algebraFactory->create($products)
    ->groupBy('category')
    ->aggregate(['geoMeanPrice' => 'geomean(price)'])
    ->toArray();
```

## Using `services.yaml` (alternative)

```yaml
# config/services.yaml
App\Aggregate\GeomeanAggregate:
    tags:
        - { name: algebra.aggregate }
```

## More examples

### Harmonic mean

```php
#[AsAggregate]
final class HarmonicMeanAggregate implements AggregateInterface
{
    public function name(): string { return 'harmonic_mean'; }

    public function compute(array $values): mixed
    {
        $nonZero = array_filter($values, fn($v) => $v != 0);
        if (empty($nonZero)) { return null; }

        return count($nonZero) / array_sum(array_map(fn($v) => 1 / $v, $nonZero));
    }
}
```

### Interquartile range (IQR)

```php
#[AsAggregate]
final class IqrAggregate implements AggregateInterface
{
    public function name(): string { return 'iqr'; }

    public function compute(array $values): mixed
    {
        if (count($values) < 4) { return null; }
        sort($values);
        $n  = count($values);
        $q1 = $values[(int) floor($n * 0.25)];
        $q3 = $values[(int) floor($n * 0.75)];
        return $q3 - $q1;
    }
}
```

### JSON array aggregation

```php
#[AsAggregate]
final class JsonArrayAggAggregate implements AggregateInterface
{
    public function name(): string { return 'json_array_agg'; }

    public function compute(array $values): mixed
    {
        return empty($values) ? null : json_encode(array_values($values), JSON_UNESCAPED_UNICODE);
    }
}
```

Usage: `'tags_json' => 'json_array_agg(tag)'` → `'["php","symfony","ddd"]'`

## How it works

1. `AlgebraBundle::build()` registers `AggregatePass` as a DI compiler pass.
2. At container compile time, `AggregatePass` finds all services tagged `algebra.aggregate`.
3. It adds `->register()` method calls to the `algebra.aggregates` definition.
4. It also injects them into `algebra.bootstrap_listener` as the `$aggregates` array.
5. On the first HTTP request, `AlgebraBootstrapListener` calls `Algebra::reset()` then
   re-registers each aggregate into the fresh `Algebra::aggregates()` singleton.

This means your custom functions work with both `Algebra::from()` and the injectable factory.
