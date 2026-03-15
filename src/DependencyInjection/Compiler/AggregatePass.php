<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Auto-registers services tagged `algebra.aggregate` in two places:
 *
 * 1. `algebra.aggregates` (AggregateRegistry) — via `register()` method calls,
 *    for direct injection into controllers and services.
 *
 * 2. `algebra.bootstrap_listener` — as the `$aggregates` array argument, so
 *    `Algebra::from()` also sees the custom aggregates after the first request.
 *
 * Usage — PHP attribute (recommended):
 * ```php
 * #[AsAggregate]
 * final class GeomeanAggregate implements AggregateInterface { ... }
 * ```
 *
 * Usage — services.yaml:
 * ```yaml
 * App\Aggregate\GeomeanAggregate:
 *     tags: [{ name: algebra.aggregate }]
 * ```
 */
final class AggregatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $tagged = $container->findTaggedServiceIds('algebra.aggregate');

        if (empty($tagged)) {
            return;
        }

        // 1. Wire into the injectable AggregateRegistry service
        if ($container->hasDefinition('algebra.aggregates')) {
            $registry = $container->getDefinition('algebra.aggregates');
            foreach ($tagged as $id => $tags) {
                $registry->addMethodCall('register', [new Reference($id)]);
            }
        }

        // 2. Wire into the bootstrap listener so Algebra::from() also picks them up
        if ($container->hasDefinition('algebra.bootstrap_listener')) {
            $listener = $container->getDefinition('algebra.bootstrap_listener');
            $refs = \array_map(
                static fn (string $id): Reference => new Reference($id),
                \array_keys($tagged)
            );
            $listener->setArgument('$aggregates', $refs);
        }
    }
}
