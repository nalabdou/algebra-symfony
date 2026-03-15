# Doctrine Adapters

The bundle auto-registers Doctrine adapters when the relevant packages are present.
No configuration required.

## Doctrine ORM — QueryBuilder

**Requires:** `doctrine/orm ^3.0`

```bash
composer require doctrine/orm
```

Pass a `QueryBuilder` directly to `algebra.factory`:

```php
use Nalabdou\Algebra\Collection\CollectionFactory;
use Doctrine\ORM\EntityManagerInterface;

final class ReportService
{
    public function __construct(
        private readonly CollectionFactory      $algebraFactory,
        private readonly EntityManagerInterface $em,
    ) {}

    public function paidOrdersByRegion(): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id, o.amount, o.status, o.region, o.createdAt')
            ->from(Order::class, 'o')
            ->where('o.createdAt > :since')
            ->setParameter('since', new \DateTime('-90 days'));

        return $this->algebraFactory->create($qb)
            ->where("item['status'] == 'paid'")
            ->groupBy('region')
            ->aggregate([
                'revenue' => 'sum(amount)',
                'orders'  => 'count(*)',
                'avg'     => 'avg(amount)',
            ])
            ->orderBy('revenue', 'desc')
            ->toArray();
    }
}
```

The query is executed once via `getArrayResult()` — each row becomes a flat
associative array which is the native algebra-php format. Apply Doctrine
`->where()` clauses for large datasets before passing to the factory.

## Doctrine Collections

**Requires:** `doctrine/collections ^2.0`

```bash
composer require doctrine/collections
```

Pass any `Doctrine\Common\Collections\Collection` to `algebra.factory`:

```php
// OneToMany relation on a User entity
$result = $this->algebraFactory->create($user->getOrders())
    ->where("item['status'] == 'paid'")
    ->orderBy('amount', 'desc')
    ->topN(10, by: 'amount')
    ->toArray();
```

Works with `ArrayCollection`, `PersistentCollection`, and any class implementing
`Doctrine\Common\Collections\Collection`.

> **Lazy loading:** Calling `toArray()` on an uninitialised `PersistentCollection`
> triggers one SQL query — expected Doctrine behaviour. Filter with `->where()`
> before aggregating to reduce the result set.

## Priority ordering

When multiple adapters are registered, they are checked in priority order:

| Priority | Adapter |
|---|---|
| 100 | `DoctrineQueryBuilderAdapter` |
| 90 | `DoctrineCollectionAdapter` |
| 0 | Custom adapters (default) |
| — | Built-in: array, generator, Traversable (always last) |

## Using with `Algebra::from()`

> Doctrine adapters are only available via the injectable `algebra.factory` service.
> `Algebra::from()` uses its own internal factory and does not include tagged adapters.

Use `algebra.factory` for all Doctrine inputs:

```php
// Correct
$this->algebraFactory->create($queryBuilder)

// Will throw InvalidArgumentException — no Doctrine adapter registered
Algebra::from($queryBuilder)
```
