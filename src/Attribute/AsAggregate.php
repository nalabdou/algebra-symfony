<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Attribute;

/**
 * Marks a class as an algebra-php aggregate function and auto-registers it
 * in the {@see \Nalabdou\Algebra\Aggregate\AggregateRegistry}.
 *
 * Apply to any class implementing {@see \Nalabdou\Algebra\Contract\AggregateInterface}.
 * The Symfony DI compiler pass ({@see \Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AggregatePass})
 * discovers all tagged services and calls `AggregateRegistry::register()` for each.
 *
 * ### Usage
 * ```php
 * use Nalabdou\Algebra\Contract\AggregateInterface;
 * use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;
 *
 * #[AsAggregate]
 * final class GeomeanAggregate implements AggregateInterface
 * {
 *     public function name(): string { return 'geomean'; }
 *
 *     public function compute(array $values): mixed
 *     {
 *         return empty($values) ? null : array_product($values) ** (1 / count($values));
 *     }
 * }
 * ```
 *
 * No services.yaml entry required. Autowiring picks it up automatically.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAggregate
{
}
