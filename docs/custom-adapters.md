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

The adapter is auto-injected into the injectable `algebra.adapter_registry` service
via the `AdapterPass` compiler pass.

```php
// Works — CsvFileAdapter is in the factory's adapter chain
$result = Algebra::from('/data/orders.csv')
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

$result = Algebra::from($stmt)
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
$top10 = Algebra::from(['__redis_zset' => 'leaderboard'])
    ->topN(10, by: 'score')
    ->toArray();
```
