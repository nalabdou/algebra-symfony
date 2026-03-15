<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects tagged adapters (sorted by priority) into the injectable CollectionFactory.
 *
 * Tagged adapters are available via `algebra.factory` injection.
 * They are not injected into `Algebra::from()` static, because algebra-php's
 * internal factory is private — the bundle does not modify algebra-php source.
 *
 * Priority: higher = checked first. Doctrine QB=100, Doctrine Collection=90, default=0.
 *
 * Usage — PHP attribute:
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
        if (!$container->hasDefinition('algebra.factory')) {
            return;
        }

        $tagged = $container->findTaggedServiceIds('algebra.adapter');

        if (empty($tagged)) {
            return;
        }

        \uasort($tagged, static function (array $a, array $b): int {
            return (int) ($b[0]['priority'] ?? 0) <=> (int) ($a[0]['priority'] ?? 0);
        });

        $refs = \array_map(
            static fn (string $id): Reference => new Reference($id),
            \array_keys($tagged)
        );

        $container->getDefinition('algebra.factory')
            ->replaceArgument('$adapters', $refs);
    }
}
