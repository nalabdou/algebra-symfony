<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Adapter;

use Doctrine\ORM\QueryBuilder;
use Nalabdou\Algebra\Contract\AdapterInterface;

/**
 * Executes a Doctrine ORM `QueryBuilder` and returns HYDRATE_ARRAY results.
 *
 * Each row is a flat associative array — the native format for algebra-php.
 * Auto-registered at priority 100 when `doctrine/orm` is installed.
 *
 * ```php
 * $qb = $em->createQueryBuilder()
 *     ->select('o.id, o.amount, o.status, o.region')
 *     ->from(Order::class, 'o')
 *     ->where('o.createdAt > :since')
 *     ->setParameter('since', new \DateTime('-30 days'));
 *
 * $result = Algebra::from($qb)
 *     ->where("item['status'] == 'paid'")
 *     ->groupBy('region')
 *     ->aggregate(['revenue' => 'sum(amount)', 'orders' => 'count(*)'])
 *     ->orderBy('revenue', 'desc')
 *     ->toArray();
 * ```
 *
 * The query executes once when `CollectionFactory::create()` resolves the input.
 * Use Doctrine `->where()` for large datasets to reduce the initial fetch.
 */
final class DoctrineQueryBuilderAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return $input instanceof QueryBuilder;
    }

    public function toArray(mixed $input): array
    {
        return $input->getQuery()->getArrayResult();
    }
}
