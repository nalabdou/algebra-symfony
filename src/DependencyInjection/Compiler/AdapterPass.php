<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires services tagged `algebra.adapter` into two places:
 *
 * 1. `algebra.adapter_registry` — via `register(adapter, priority)` method calls,
 *    so the injectable `CollectionFactory` accepts custom input types.
 *
 * 2. `algebra.bootstrap_listener` — as the `$adapters` array argument, so
 *    `Algebra::from()` also accepts custom types after the first request.
 *
 * This means a single `#[AsAlgebraAdapter]` attribute makes a custom adapter
 * available everywhere — both the injectable factory and the static entry point.
 *
 * Priority: higher = checked first. Doctrine QB=100, Doctrine Collection=90, default=0.
 *
 * Usage — PHP attribute (recommended):
 * ```php
 * #[AsAlgebraAdapter(priority: 50)]
 * final class CsvFileAdapter implements AdapterInterface { ... }
 * ```
 *
 * Usage — services.yaml:
 * ```yaml
 * App\Adapter\CsvFileAdapter:
 *     tags: [{ name: algebra.adapter, priority: 50 }]
 * ```
 */
final class AdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $tagged = $container->findTaggedServiceIds('algebra.adapter');

        if (empty($tagged)) {
            return;
        }

        // 1. Wire into the AdapterRegistry service (for injectable CollectionFactory)
        if ($container->hasDefinition('algebra.adapter_registry')) {
            $registry = $container->getDefinition('algebra.adapter_registry');

            foreach ($tagged as $id => $tags) {
                $priority = (int) ($tags[0]['priority'] ?? 0);
                $registry->addMethodCall('register', [new Reference($id), $priority]);
            }
        }

        // 2. Wire into the bootstrap listener (for Algebra::from() static)
        if ($container->hasDefinition('algebra.bootstrap_listener')) {
            $entries = [];
            $listener = $container->getDefinition('algebra.bootstrap_listener');

            foreach ($tagged as $id => $tags) {
                $priority = (int) ($tags[0]['priority'] ?? 0);
                $entries[] = ['adapter' => new Reference($id), 'priority' => $priority];
            }

            $listener->setArgument('$adapters', $entries);
        }
    }
}
