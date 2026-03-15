# Custom Adapters

Teach `CollectionFactory` to accept new input types with a single attribute.

## Using `#[AsAlgebraAdapter]`

Implement `AdapterInterface` and add `#[AsAlgebraAdapter]`:

```php
// src/Adapter/CsvFileAdapter.php
namespace App\Adapter;

use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;

#[AsAlgebraAdapter(priority: 50)]
final class CsvFileAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return \is_string($input)
            && \str_ends_with($input, '.csv')
            && \file_exists($input);
    }

    public function toArray(mixed $input): array
    {
        $rows   = [];
        $handle = \fopen($input, 'rb');
        $header = \fgetcsv($handle);

        while (($row = \fgetcsv($handle)) !== false) {
            $rows[] = \array_combine($header, $row);
        }

        \fclose($handle);

        return $rows;
    }
}
```

The adapter is auto-injected into the injectable `algebra.factory` service
via the `AdapterPass` compiler pass.

```php
// Works — CsvFileAdapter is in the factory's adapter chain
$result = $this->algebraFactory->create('/data/orders.csv')
    ->where("item['status'] == 'paid'")
    ->groupBy('region')
    ->aggregate(['revenue' => 'sum(amount)'])
    ->toArray();
```

## Priority

Higher priority = checked first. The `priority` argument controls insertion order:

| Priority | Use case |
|---|---|
| 100+ | Framework adapters (reserved for Doctrine QB, Eloquent, etc.) |
| 50–99 | Third-party sources (CSV, Redis, REST API) |
| 1–49 | Application-specific adapters |
| 0 | Default — checked last before built-ins |

Built-in array/generator/Traversable adapters are always tried after all tagged adapters.

## Using `services.yaml` (alternative)

```yaml
# config/services.yaml
App\Adapter\CsvFileAdapter:
    tags:
        - { name: algebra.adapter, priority: 50 }
```

## More examples

### PDO Statement

```php
#[AsAlgebraAdapter(priority: 80)]
final class PdoStatementAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return $input instanceof \PDOStatement;
    }

    public function toArray(mixed $input): array
    {
        return $input->fetchAll(\PDO::FETCH_ASSOC);
    }
}

// Usage
$stmt = $pdo->prepare('SELECT id, amount, status FROM orders WHERE created_at > ?');
$stmt->execute([$since]);

$result = $this->algebraFactory->create($stmt)
    ->groupBy('status')
    ->aggregate(['total' => 'sum(amount)'])
    ->toArray();
```

### Redis Sorted Set

```php
#[AsAlgebraAdapter(priority: 60)]
final class RedisSortedSetAdapter implements AdapterInterface
{
    public function __construct(private readonly \Redis $redis) {}

    public function supports(mixed $input): bool
    {
        return is_array($input) && isset($input['__redis_zset']);
    }

    public function toArray(mixed $input): array
    {
        $members = $this->redis->zRangeWithScores($input['__redis_zset'], 0, -1);
        return array_map(
            fn($member, $score) => ['member' => $member, 'score' => $score],
            array_keys($members),
            $members
        );
    }
}

// Usage
$top10 = $this->algebraFactory->create(['__redis_zset' => 'leaderboard'])
    ->topN(10, by: 'score')
    ->toArray();
```

## Important: `Algebra::from()` limitation

> Custom adapters are only available via the **injectable** `algebra.factory` service.
> `Algebra::from()` uses its own private internal factory — the bundle cannot
> inject tagged adapters into it without modifying algebra-php source code.

```php
// Correct — uses the DI-configured factory with all tagged adapters
$this->algebraFactory->create('/data/orders.csv')

// Throws InvalidArgumentException — Algebra::from() has no CSV adapter
Algebra::from('/data/orders.csv')
```

For `Algebra::from()` with array/generator/Traversable inputs, it always works as documented.
