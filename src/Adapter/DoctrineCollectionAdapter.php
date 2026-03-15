<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Adapter;

use Doctrine\Common\Collections\Collection;
use Nalabdou\Algebra\Contract\AdapterInterface;

/**
 * Converts any Doctrine `Collection` into a plain PHP array for algebra-php.
 *
 * Covers `ArrayCollection`, `PersistentCollection`, and any custom class
 * implementing `Doctrine\Common\Collections\Collection`.
 *
 * Auto-registered at priority 90 when `doctrine/collections` is installed.
 *
 * ```php
 * $result = Algebra::from($user->getOrders())   // PersistentCollection
 *     ->where("item['status'] == 'paid'")
 *     ->orderBy('amount', 'desc')
 *     ->toArray();
 * ```
 *
 * Accessing an uninitialised `PersistentCollection` triggers one SQL query.
 * Apply Doctrine criteria before passing the collection to reduce the fetch size.
 */
final class DoctrineCollectionAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return $input instanceof Collection;
    }

    public function toArray(mixed $input): array
    {
        return \array_values($input->toArray());
    }
}
