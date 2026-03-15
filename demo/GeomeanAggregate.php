<?php

declare(strict_types=1);

namespace App\Aggregate;

use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;

/**
 * Demo: custom aggregate auto-registered via #[AsAggregate].
 *
 * Just add the attribute — no services.yaml entry, no manual register() call.
 * The AlgebraBundle compiler pass discovers and registers it automatically.
 *
 * Usage in a pipeline:
 * ```php
 * Algebra::from($products)
 *     ->groupBy('category')
 *     ->aggregate(['geoMeanPrice' => 'geomean(price)'])
 *     ->toArray();
 * ```
 */
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

        $product = \array_product(\array_map('\abs', $values));

        return $product ** (1 / \count($values));
    }
}
